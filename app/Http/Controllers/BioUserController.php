<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BioUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BioTimeSummaryExport;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Exports\BioTimeMonthlyDetailExport;
use App\Exports\BioTimeMonthlyWorkbookExport;

class BioUserController extends Controller
{

    public function index(Request $request)
    {
        try {
            // ------------ Inputs ------------
            $month = (int) $request->input('month', (int) date('m'));
            $year  = (int) $request->input('year',  (int) date('Y'));

            $departmentId = $request->input('department_id'); // nullable
            $deptParam    = ($departmentId === '' ? null : $departmentId);
            $search       = trim((string) $request->input('search', ''));

            if ($month < 1 || $month > 12)  throw new \Exception('Invalid month value.');
            if ($year  < 2000 || $year  > (int) date('Y')) throw new \Exception('Invalid year value.');

            // Ensure BioTime connection uses proper TZ
            $this->setBioSession();

            // ------------ Tables ------------
            $empTable  = 'personnel_employee';
            $deptTable = 'personnel_department';
            $trxTable  = 'iclock_transaction';

            // ------------ Column pickers ------------
            $pick = function (string $table, array $candidates) {
                foreach ($candidates as $c) {
                    if (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($table, $c)) return $c;
                }
                return null;
            };

            // employee code
            $empCodeColEmp = $pick($empTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
            if (!$empCodeColEmp) {
                throw new \Exception("Could not find an employee code column on {$empTable} (tried emp_code, emp_id, employee_code, user_id, userid, pin).");
            }

            // employee name expression
            $hasFirst = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'first_name');
            $hasLast  = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'last_name');
            if ($hasFirst && $hasLast) {
                $nameExpr = "COALESCE(e.first_name,'') || ' ' || COALESCE(e.last_name,'')";
            } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'name')) {
                $nameExpr = "COALESCE(e.name,'Unknown')";
            } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'full_name')) {
                $nameExpr = "COALESCE(e.full_name,'Unknown')";
            } else {
                $nameExpr = "COALESCE(e.{$empCodeColEmp}::text,'Unknown')";
            }

            // photo column (optional)
            $photoCol = $pick($empTable, ['photo', 'picture', 'image', 'photo_path', 'avatar', 'image_path', 'img']);

            // department join strategy
            $empDeptIdCol   = $pick($empTable, ['dept_id', 'department_id', 'deptid']);
            $empDeptCodeCol = $pick($empTable, ['dept_code', 'department_code']);
            $deptIdCol      = $pick($deptTable, ['id']);
            $deptCodeCol    = $pick($deptTable, ['dept_code']);
            $deptNameCol    = $pick($deptTable, ['dept_name', 'name']);

            if (!$deptNameCol) {
                throw new \Exception("Could not find department name column on {$deptTable} (tried dept_name, name).");
            }
            if (!$deptIdCol && !$deptCodeCol) {
                throw new \Exception("Could not find a joinable department key on {$deptTable} (tried id, dept_code).");
            }

            // transactions columns
            $trxTimeCol = $pick($trxTable, ['punch_time', 'checktime', 'log_time', 'time']);
            if (!$trxTimeCol) {
                throw new \Exception("Could not find punch time column on {$trxTable} (tried punch_time, checktime, log_time, time).");
            }
            $trxEmpCol = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, $empCodeColEmp)
                ? $empCodeColEmp
                : $pick($trxTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
            if (!$trxEmpCol) {
                throw new \Exception("Could not find employee ref column on {$trxTable} (tried {$empCodeColEmp}, emp_code, emp_id, employee_code, user_id, userid, pin).");
            }

            $hasPunchState = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, 'punch_state');
            $stateClause   = $hasPunchState ? "AND t.punch_state IN ('0','1')" : "";

            // build department JOIN clause + expose u.dept_id for filtering
            if ($empDeptIdCol && $deptIdCol) {
                $deptJoin = "LEFT JOIN {$deptTable} d ON d.{$deptIdCol} = e.{$empDeptIdCol}";
                $deptIdSelect = "d.{$deptIdCol} AS dept_id";
                $deptNameSelect = "d.{$deptNameCol}";
            } elseif ($empDeptCodeCol && $deptCodeCol) {
                $deptJoin = "LEFT JOIN {$deptTable} d ON d.{$deptCodeCol} = e.{$empDeptCodeCol}";
                $deptIdSelect = $deptIdCol ? "d.{$deptIdCol} AS dept_id" : "NULL::int AS dept_id";
                $deptNameSelect = "d.{$deptNameCol}";
            } else {
                $deptJoin = "";
                $deptIdSelect = "NULL::int AS dept_id";
                $deptNameSelect = "NULL";
            }

            // departments for dropdown
            $departments = $this->retryQuery(function () use ($deptTable, $deptIdCol, $deptNameCol) {
                return \Illuminate\Support\Facades\DB::connection('bio')->select("
                SELECT {$deptIdCol} AS id, {$deptNameCol} AS dept_name
                FROM {$deptTable}
                ORDER BY {$deptNameCol}
            ");
            });

            // ------------ Total Hours Calculation ------------
            $totalHoursSql = "
                WITH tx AS (
                    SELECT
                        t.{$trxEmpCol} AS userid,
                        t.{$trxTimeCol} AS ts,
                        " . ($hasPunchState ? "t.punch_state::int" : "ROW_NUMBER() OVER (PARTITION BY t.{$trxEmpCol} ORDER BY t.{$trxTimeCol}) % 2") . " AS state
                    FROM {$trxTable} t
                    LEFT JOIN {$empTable} e ON e.{$empCodeColEmp} = t.{$trxEmpCol}
                    {$deptJoin}
                    WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                      AND EXTRACT(YEAR FROM t.{$trxTimeCol}) = ?
                      " . ($hasPunchState ? "AND t.punch_state IN ('0','1')" : "") . "
                      " . ($deptParam ? "AND COALESCE(d.{$deptIdCol}, -1) = ?" : "") . "
                      " . ($search ? "AND ({$nameExpr} ILIKE ? OR t.{$trxEmpCol}::text ILIKE ? OR COALESCE({$deptNameSelect}, '') ILIKE ?)" : "") . "
                ),
                ord AS (
                    SELECT
                        userid,
                        ts,
                        state,
                        LEAD(ts) OVER (PARTITION BY userid ORDER BY ts) AS next_ts,
                        LEAD(state) OVER (PARTITION BY userid ORDER BY ts) AS next_state
                    FROM tx
                ),
                sessions AS (
                    SELECT
                        userid,
                        ts AS start_ts,
                        next_ts AS end_ts,
                        EXTRACT(EPOCH FROM (next_ts - ts)) / 60.0 AS minutes
                    FROM ord
                    WHERE next_ts IS NOT NULL
                      AND next_ts > ts
                      AND (next_ts - ts) <= INTERVAL '36 hours'
                      " . ($hasPunchState ? "AND state = 0 AND next_state = 1" : "AND state = 0") . "
                )
                SELECT COALESCE(SUM(minutes), 0)::int AS total_min
                FROM sessions
            ";

            $totalHoursBindings = [$month, $year];
            if ($deptParam) {
                $totalHoursBindings[] = $deptParam;
            }
            if ($search) {
                $totalHoursBindings[] = "%{$search}%";
                $totalHoursBindings[] = "%{$search}%";
                $totalHoursBindings[] = "%{$search}%";
            }

            $totalHoursResult = $this->retryQuery(
                fn() => \Illuminate\Support\Facades\DB::connection('bio')->selectOne($totalHoursSql, $totalHoursBindings)
            );

            $totalMin = $totalHoursResult->total_min ?? 0;
            Log::info('Total hours calculation', [
                'total_min' => $totalMin,
                'bindings' => $totalHoursBindings,
                'sql' => $totalHoursSql,
                'user_id' => $search === 'CCBRT0768' ? 'CCBRT0768' : 'all'
            ]);

            $toHHMM = function (int $mins) {
                $mins = max(0, $mins);
                $h = intdiv($mins, 60);
                $m = $mins % 60;
                return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            };
            $totalHours = $toHHMM($totalMin);

            // ------------ Summary (month/year) ------------
            $summarySql = "
            WITH base AS (
                SELECT t.{$trxEmpCol} AS userid
                FROM {$trxTable} t
                WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  {$stateClause}
            ),
            users AS (
                SELECT DISTINCT
                    b.userid,
                    {$nameExpr} AS name,
                    {$deptIdSelect},
                    " . ($deptJoin ? $deptNameSelect : "NULL") . " AS department
                    " . ($photoCol ? ", e.{$photoCol} AS photo_raw" : ", NULL AS photo_raw") . "
                FROM base b
                LEFT JOIN {$empTable} e ON e.{$empCodeColEmp} = b.userid
                {$deptJoin}
            ),
            punches AS (
                SELECT
                    t.{$trxEmpCol} AS userid,
                    COUNT(DISTINCT DATE(t.{$trxTimeCol})) AS punch_days,
                    MAX(t.{$trxTimeCol}) AS latest_punch
                FROM {$trxTable} t
                WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  {$stateClause}
                GROUP BY t.{$trxEmpCol}
            )
            SELECT
                u.userid,
                u.name,
                COALESCE(u.department, 'Unknown')     AS department,
                COALESCE(p.punch_days, 0)             AS punch_days,
                COALESCE(p.latest_punch::text, 'N/A') AS latest_punch,
                u.photo_raw
            FROM users u
            LEFT JOIN punches p ON p.userid = u.userid
            WHERE (?::int IS NULL OR COALESCE(u.dept_id, -1) = ?::int)
              AND (? = '' OR u.userid::text ILIKE ? OR u.name ILIKE ? OR COALESCE(u.department,'') ILIKE ?)
            ORDER BY u.department NULLS LAST, u.name
        ";

            $bindings = [
                $month,
                $year, // base
                $month,
                $year, // punches
                $deptParam,
                $deptParam,
                $search,
                "%{$search}%",
                "%{$search}%",
                "%{$search}%",
            ];

            $summaryData = $this->retryQuery(
                fn() => \Illuminate\Support\Facades\DB::connection('bio')->select($summarySql, $bindings)
            );

            // ---------- Build photo_url & compute latest punch + name ----------
            $publicPath  = public_path();
            $storagePath = storage_path('app/public/photo');

            $makeUrl = function (string $absPath) use ($publicPath) {
                $rel = ltrim(str_replace([$publicPath, '\\'], ['', '/'], $absPath), '/');
                return asset($rel);
            };

            foreach ($summaryData as $row) {
                $row->photo_url = null;

                $raw = trim((string)($row->photo_raw ?? ''));
                $code = (string)$row->userid;

                if ($raw && preg_match('#^https?://#i', $raw)) {
                    $row->photo_url = $raw;
                    continue;
                }

                $candidates = [];
                if ($raw) {
                    $basename = ltrim($raw, '/');
                    $candidates[] = public_path($basename);
                    $candidates[] = public_path('photo/' . basename($basename));
                    $candidates[] = $storagePath . DIRECTORY_SEPARATOR . basename($basename);
                }
                foreach (['jpg', 'png', 'jpeg'] as $ext) {
                    $candidates[] = public_path("photo/{$code}.{$ext}");
                    $candidates[] = $storagePath . DIRECTORY_SEPARATOR . "{$code}.{$ext}";
                }

                foreach ($candidates as $abs) {
                    if (is_file($abs)) {
                        if (str_starts_with($abs, $storagePath)) {
                            $row->photo_url = asset('storage/photo/' . basename($abs));
                        } else {
                            $row->photo_url = $makeUrl($abs);
                        }
                        break;
                    }
                }
            }

            $latestRow = null;
            foreach ($summaryData as $r) {
                if ($r->latest_punch === 'N/A') continue;
                if (!$latestRow || $r->latest_punch > $latestRow->latest_punch) {
                    $latestRow = $r;
                }
            }
            $latestPunchText = $latestRow
                ? \Carbon\Carbon::parse($latestRow->latest_punch)->format('Y-m-d H:i:s')
                : null;
            $latestPunchName = $latestRow->name ?? null;

            // ---------- RAW punches for a selected user (AJAX) ----------
            $userId = $request->input('user_id');
            if ($userId && $month && $year) {
                $directionExpr = $hasPunchState
                    ? "CASE WHEN t.punch_state='0' THEN 'Check In'
                        WHEN t.punch_state='1' THEN 'Check Out'
                        ELSE 'Punch' END"
                    : "'Punch'";

                $rawSql = "
                SELECT
                    e.{$empCodeColEmp}::text AS emp_code,
                    {$nameExpr}              AS emp_name,
                    COALESCE(" . ($deptJoin ? $deptNameSelect : "NULL") . ", 'Unknown') AS department,
                    DATE(t.{$trxTimeCol})    AS punch_date,
                    TO_CHAR(t.{$trxTimeCol}, 'HH24:MI:SS') AS punch_time,
                    {$directionExpr}         AS direction,
                    '0'                      AS status_code,
                    'Device'                 AS source
                FROM {$trxTable} t
                JOIN {$empTable} e ON e.{$empCodeColEmp} = t.{$trxEmpCol}
                " . ($deptJoin ? $deptJoin : "") . "
                WHERE t.{$trxEmpCol} = ?
                  AND EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  {$stateClause}
                ORDER BY t.{$trxTimeCol} ASC
            ";

                $rawRows = $this->retryQuery(
                    fn() => \Illuminate\Support\Facades\DB::connection('bio')->select($rawSql, [$userId, $month, $year])
                );

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['rawRecords' => $rawRows]);
                }
            }

            // ---------- Render ----------
            return view('biotime.index', [
                'records'         => $summaryData,
                'month'           => $month,
                'year'            => $year,
                'selectedUserId'  => $userId ?? null,
                'departments'     => $departments,
                'department_id'   => $departmentId,
                'search'          => $search,
                'latestPunchText' => $latestPunchText,
                'latestPunchName' => $latestPunchName,
                'totalHours'      => $totalHours,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = method_exists($this, 'getDetailedErrorMessage')
                ? $this->getDetailedErrorMessage($e)
                : $e->getMessage();

            \Log::error('BioTime query failed', ['error' => $e->getMessage(), 'details' => $errorMessage]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            $errorMessage = 'An unexpected error occurred: ' . $e->getMessage();
            \Log::error('Unexpected error in BioUserController', ['error' => $e->getMessage()]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }
    /** Ensure the 'bio' session uses your TZ (so dates/times match Africa/Nairobi) */
    private function setBioSession(): void
    {
        try {
            $tz = env('DB_BIO_TIMEZONE', 'Africa/Nairobi');
            \Illuminate\Support\Facades\DB::connection('bio')->getPdo();
            \Illuminate\Support\Facades\DB::connection('bio')->statement("SET TIME ZONE '{$tz}'");

            // Optional: if BioTime uses a non-public schema, set it here:
            if ($schema = env('DB_BIO_SCHEMA')) {
                \Illuminate\Support\Facades\DB::connection('bio')->statement("SET search_path TO {$schema}");
            }
        } catch (\Throwable $e) {
            // ignore if connection not used
        }
    }


    public function export(Request $request)
    {
        $request->validate([
            'month'         => ['required', 'integer', 'between:1,12'],
            'year'          => ['required', 'integer', 'min:2000'],
            'department_id' => ['nullable'],
            'user_ids'      => ['nullable', 'array'],
        ]);

        $month  = (int) $request->input('month');
        $year   = (int) $request->input('year');
        $deptId = $request->input('department_id');
        $userIdsInput = $request->input('user_ids', []);

        // Keep Bio DB in the right timezone
        if (method_exists($this, 'setBioSession')) {
            $this->setBioSession();
        }

        // ------------ Tables ------------
        $empTable  = 'personnel_employee';
        $deptTable = 'personnel_department';
        $trxTable  = 'iclock_transaction';

        // ------------ Helper to pick a present column ------------
        $pick = function (string $table, array $candidates) {
            foreach ($candidates as $c) {
                if (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($table, $c)) {
                    return $c;
                }
            }
            return null;
        };

        // Employee key present on employee table
        $empKeyColEmp = $pick($empTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
        if (!$empKeyColEmp) {
            return back()->with('error', "Export failed: could not find an employee code column on {$empTable}.");
        }

        // Transaction columns
        $trxTimeCol = $pick($trxTable, ['punch_time', 'checktime', 'log_time', 'time']);
        if (!$trxTimeCol) {
            return back()->with('error', "Export failed: no punch time column on {$trxTable}.");
        }
        $empKeyColTrx = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, $empKeyColEmp)
            ? $empKeyColEmp
            : $pick($trxTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
        if (!$empKeyColTrx) {
            return back()->with('error', "Export failed: employee reference column not found on {$trxTable}.");
        }

        // Name expression (dynamic)
        $hasFirst = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'first_name');
        $hasLast  = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'last_name');
        if ($hasFirst && $hasLast) {
            $nameExpr = "COALESCE(e.first_name,'') || ' ' || COALESCE(e.last_name,'')";
        } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'name')) {
            $nameExpr = "COALESCE(e.name,'Unknown')";
        } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'full_name')) {
            $nameExpr = "COALESCE(e.full_name,'Unknown')";
        } else {
            $nameExpr = "COALESCE(e.{$empKeyColEmp}::text,'Unknown')";
        }

        // Optional department join
        $empDeptIdCol   = $pick($empTable, ['dept_id', 'department_id', 'deptid']);
        $empDeptCodeCol = $pick($empTable, ['dept_code', 'department_code']);
        $deptIdCol      = $pick($deptTable, ['id']);
        $deptCodeCol    = $pick($deptTable, ['dept_code']);
        $deptNameCol    = $pick($deptTable, ['dept_name', 'name']);

        if ($deptNameCol && $empDeptIdCol && $deptIdCol) {
            $deptJoin  = "LEFT JOIN {$deptTable} dp ON dp.{$deptIdCol} = e.{$empDeptIdCol}";
            $deptNameSelect = "dp.{$deptNameCol}";
        } elseif ($deptNameCol && $empDeptCodeCol && $deptCodeCol) {
            $deptJoin  = "LEFT JOIN {$deptTable} dp ON dp.{$deptCodeCol} = e.{$empDeptCodeCol}";
            $deptNameSelect = "dp.{$deptNameCol}";
        } else {
            $deptJoin  = "";
            $deptNameSelect = "NULL";
        }

        // ---- Decide users to export (same logic as before) ----
        $targetUserIds = [];

        if (!empty($userIdsInput)) {
            $targetUserIds = array_values(array_unique($userIdsInput));
        } elseif (!empty($deptId)) {
            if (!$empDeptIdCol || !$deptIdCol) {
                return back()->with('error', 'Export failed: department join not possible; cannot filter by department.');
            }
            $rows = $this->retryQuery(function () use ($trxTable, $trxTimeCol, $empKeyColTrx, $empTable, $empKeyColEmp, $empDeptIdCol, $deptTable, $deptIdCol, $deptId, $month, $year) {
                return \Illuminate\Support\Facades\DB::connection('bio')->select("
                SELECT DISTINCT t.{$empKeyColTrx} AS userid
                FROM {$trxTable} t
                JOIN {$empTable} e ON e.{$empKeyColEmp} = t.{$empKeyColTrx}
                LEFT JOIN {$deptTable} d ON d.{$deptIdCol} = e.{$empDeptIdCol}
                WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  AND COALESCE(d.{$deptIdCol}, -1) = ?::int
            ", [$month, $year, $deptId]);
            });
            $targetUserIds = array_map(fn($r) => $r->userid, $rows);
        } else {
            $rows = $this->retryQuery(function () use ($trxTable, $trxTimeCol, $empKeyColTrx, $month, $year) {
                return \Illuminate\Support\Facades\DB::connection('bio')->select("
                SELECT DISTINCT t.{$empKeyColTrx} AS userid
                FROM {$trxTable} t
                WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
            ", [$month, $year]);
            });
            $targetUserIds = array_map(fn($r) => $r->userid, $rows);
        }

        if (empty($targetUserIds)) {
            return back()->with('error', 'No matching users found to export for those filters.');
        }

        // ---- Pairing params (complete cycles only) ----
        $hasPunchState = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, 'punch_state');
        $maxGapHours   = (int) env('BIOTIME_MAX_GAP_HOURS', 36);
        $inState       = (int) env('BIOTIME_IN_STATE', 0);  // change to 1 if your device logs 1=IN
        $outState      = (int) env('BIOTIME_OUT_STATE', 1); // change to 0 if your device logs 0=OUT

        // Direction expression
        $directionExpr = $hasPunchState
            ? "CASE WHEN t.punch_state='{$inState}' THEN 'Check In' WHEN t.punch_state='{$outState}' THEN 'Check Out' ELSE 'Punch' END"
            : "'Punch'";

        $stateClause = $hasPunchState ? "AND t.punch_state IN ('{$inState}','{$outState}')" : "";

        // ---- Build IN (...) placeholders ----
        $inPlaceholders = implode(',', array_fill(0, count($targetUserIds), '?'));
        $bindings = array_merge([$month, $year], $targetUserIds);

        // ---- Raw transactions (ALL punches, including unpaired) ----
        $rawSql = "
            SELECT
                e.{$empKeyColEmp} AS userid,
                {$nameExpr} AS name,
                COALESCE({$deptNameSelect}, 'Unknown') AS department,
                DATE(t.{$trxTimeCol}) AS punch_date,
                TO_CHAR(t.{$trxTimeCol}, 'HH24:MI:SS') AS punch_time,
                {$directionExpr} AS direction
            FROM {$trxTable} t
            JOIN {$empTable} e ON e.{$empKeyColEmp} = t.{$empKeyColTrx}
            {$deptJoin}
            WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
              AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
              AND t.{$empKeyColTrx} IN ({$inPlaceholders})
              {$stateClause}
            ORDER BY department NULLS LAST, name, t.{$trxTimeCol}
        ";

        $rawRows = $this->retryQuery(
            fn() => \Illuminate\Support\Facades\DB::connection('bio')->select($rawSql, $bindings)
        );

        if (empty($rawRows)) {
            return back()->with('error', 'Nothing to export (no punches found for the selection).');
        }

        // ---- Paired sessions for totals (same as before) ----
        $dailySql = "
        WITH tx AS (
            SELECT
                t.{$empKeyColTrx} AS userid,
                t.{$trxTimeCol}   AS ts
                " . ($hasPunchState ? ", t.punch_state::int AS state" : "") . "
            FROM {$trxTable} t
            WHERE EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
              AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
              AND t.{$empKeyColTrx} IN ({$inPlaceholders})
              " . ($hasPunchState ? "AND t.punch_state IN ('{$inState}','{$outState}')" : "") . "
        ),
        ord AS (
            SELECT
                userid,
                ts
                " . ($hasPunchState ? ", state" : ", ROW_NUMBER() OVER (PARTITION BY userid ORDER BY ts) AS rn") . ",
                LEAD(ts)    OVER (PARTITION BY userid ORDER BY ts) AS next_ts
                " . ($hasPunchState ? ", LEAD(state) OVER (PARTITION BY userid ORDER BY ts) AS next_state" : "") . "
            FROM tx
        ),
        sessions AS (
            SELECT
                userid,
                ts      AS start_ts,
                next_ts AS end_ts,
                EXTRACT(EPOCH FROM (next_ts - ts)) / 60.0 AS minutes
            FROM ord
            WHERE next_ts IS NOT NULL
              AND next_ts > ts
              AND (next_ts - ts) <= INTERVAL '{$maxGapHours} hours'
              " . ($hasPunchState
            ? "AND state = {$inState} AND next_state = {$outState}"
            : "AND (rn % 2) = 1") . "
        ),
        daily AS (
            SELECT
                userid,
                DATE(start_ts) AS punch_date,
                MIN(start_ts)  AS first_in,
                MAX(end_ts)    AS last_out,
                ROUND(SUM(minutes))::int AS total_min
            FROM sessions
            GROUP BY userid, DATE(start_ts)
        )
        SELECT
            d.userid,
            {$nameExpr} AS name,
            COALESCE(" . ($deptNameSelect ?: "NULL") . ", 'Unknown') AS department,
            d.punch_date,
            TO_CHAR(d.first_in, 'HH24:MI:SS') AS punch_in,
            TO_CHAR(d.last_out, 'HH24:MI:SS') AS punch_out,
            d.total_min
        FROM daily d
        JOIN {$empTable} e ON e.{$empKeyColEmp} = d.userid
        " . ($deptJoin ?: "") . "
        ORDER BY department NULLS LAST, name, d.punch_date
    ";

        $dailyRowsRaw = $this->retryQuery(
            fn() => \Illuminate\Support\Facades\DB::connection('bio')->select($dailySql, $bindings)
        );

        // ---- Build collections for Excel ----
        $toHHMM = function (int $mins) {
            $mins = max(0, $mins);
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
        };

        // Raw punches collection
        $rawCollection = collect($rawRows)->map(function ($r) {
            return [
                (string) ($r->userid ?? ''),
                (string) ($r->name ?? ''),
                (string) ($r->department ?? 'Unknown'),
                (string) ($r->punch_date ?? ''),
                (string) ($r->punch_time ?? ''),
                (string) ($r->direction ?? 'Punch'),
            ];
        });

        // Summary collection (from paired daily)
        $grouped = collect($dailyRowsRaw)->groupBy(fn($r) => $r->userid);
        $summaryCollection = collect();
        $grandTotalMin = 0;

        foreach ($grouped as $userid => $rows) {
            $name = $rows->first()->name ?? '';
            $dept = $rows->first()->department ?? 'Unknown';
            $days = $rows->count();
            $totalMin = (int) $rows->sum('total_min');
            $grandTotalMin += $totalMin;

            $summaryCollection->push([
                (string) $userid,
                (string) $name,
                (string) $dept,
                (int) $days,
                $toHHMM($totalMin),
            ]);
        }
        $grandTotalHHMM = $toHHMM($grandTotalMin);

        // Append a grand total row
        $summaryCollection->push(['', 'ALL SELECTED', '', '', $grandTotalHHMM]);

        // ---- Build and stream XLSX (one sheet with raw + summary) ----
        $filename = 'BioTime_AllTransactions_' . $year . '_' . sprintf('%02d', $month) . '.xlsx';

        $export = new class($rawCollection, $summaryCollection) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\WithStyles,
            \Maatwebsite\Excel\Concerns\WithEvents
        {
            protected \Illuminate\Support\Collection $raw;
            protected \Illuminate\Support\Collection $summary;

            public function __construct($raw, $summary)
            {
                $this->raw = $raw;
                $this->summary = $summary;
            }

            public function title(): string
            {
                return 'Staff Attendance Summary';
            }

            public function headings(): array
            {
                return ['User ID', 'Name', 'Department', 'Date', 'Time', 'Direction'];
            }

            public function collection()
            {
                // Combine raw + blank row + summary headings + summary
                $summaryHeadings = collect([
                    ['', '', '', '', '', ''], // Blank row
                    ['User ID', 'Name', 'Department', 'Punch Days', 'Total Hours (HH:MM)', '']
                ]); // Summary headings

                return $this->raw->concat($summaryHeadings)->concat($this->summary);
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                $rawHeaderRow = 1;
                $summaryStartRow = $this->raw->count() + 3; // After raw + blank + summary heading

                // Style raw headers
                $sheet->getStyle("A{$rawHeaderRow}:F{$rawHeaderRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$rawHeaderRow}:F{$rawHeaderRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEFEFEF');

                // Style summary headers
                $summaryHeaderRow = $summaryStartRow - 1;
                $sheet->getStyle("A{$summaryHeaderRow}:F{$summaryHeaderRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$summaryHeaderRow}:F{$summaryHeaderRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9EAD3');

                // Bold the last row (grand total)
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A{$lastRow}:F{$lastRow}")->getFont()->setBold(true);

                // Auto-size columns
                foreach (range('A', 'F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                return [];
            }

            public function registerEvents(): array
            {
                return [
                    \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                        // Optional: Add borders or other enhancements for user-friendliness
                        $sheet = $event->sheet->getDelegate();
                        $highestRow = $sheet->getHighestRow();
                        $highestColumn = $sheet->getHighestColumn();

                        // Add thin borders to all cells
                        $styleArray = [
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => ['argb' => 'FF000000'],
                                ],
                            ],
                        ];
                        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray($styleArray);
                    },
                ];
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function show(Request $request, $userId)
    {
        try {
            $days  = (int) $request->input('days', 7);
            $month = (int) $request->input('month', (int) date('m'));
            $year  = (int) $request->input('year',  (int) date('Y'));

            if ($month < 1 || $month > 12)  throw new \Exception('Invalid month value.');
            if ($year  < 2000 || $year  > (int) date('Y')) throw new \Exception('Invalid year value.');

            if (method_exists($this, 'setBioSession')) {
                $this->setBioSession();
            }

            $empTable = 'personnel_employee';
            $trxTable = 'iclock_transaction';

            $pick = function (string $table, array $candidates) {
                foreach ($candidates as $c) {
                    if (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($table, $c)) return $c;
                }
                return null;
            };

            // Employee code
            $empCodeColEmp = $pick($empTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
            if (!$empCodeColEmp) {
                throw new \Exception("Could not find an employee code column on {$empTable} (tried emp_code, emp_id, employee_code, user_id, userid, pin).");
            }

            // Name expr
            $hasFirst = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'first_name');
            $hasLast  = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'last_name');
            if ($hasFirst && $hasLast) {
                $nameExpr = "COALESCE(e.first_name,'') || ' ' || COALESCE(e.last_name,'')";
            } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'name')) {
                $nameExpr = "COALESCE(e.name,'Unknown')";
            } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'full_name')) {
                $nameExpr = "COALESCE(e.full_name,'Unknown')";
            } else {
                $nameExpr = "COALESCE(e.{$empCodeColEmp}::text,'Unknown')";
            }

            // Fetch user display info
            $userRow = $this->retryQuery(function () use ($empTable, $empCodeColEmp, $nameExpr, $userId) {
                $row = \Illuminate\Support\Facades\DB::connection('bio')->selectOne("
                SELECT
                    {$nameExpr} AS name,
                    e.{$empCodeColEmp} AS userid
                FROM {$empTable} e
                WHERE e.{$empCodeColEmp} = ?
                LIMIT 1
            ", [$userId]);

                return $row ?: (object) ['userid' => $userId, 'name' => 'Unknown'];
            });

            // Transaction columns
            $trxTimeCol = $pick($trxTable, ['punch_time', 'checktime', 'log_time', 'time']);
            if (!$trxTimeCol) {
                throw new \Exception("Could not find punch time column on {$trxTable} (tried punch_time, checktime, log_time, time).");
            }
            $trxEmpCol = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, $empCodeColEmp)
                ? $empCodeColEmp
                : $pick($trxTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
            if (!$trxEmpCol) {
                throw new \Exception("Could not find employee ref column on {$trxTable} (tried {$empCodeColEmp}, emp_code, emp_id, employee_code, user_id, userid, pin).");
            }

            // Pairing parameters
            $hasPunchState = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, 'punch_state');
            $maxGapHours   = (int) env('BIOTIME_MAX_GAP_HOURS', 36);
            $inState       = (int) env('BIOTIME_IN_STATE', 0);  // 0 by default
            $outState      = (int) env('BIOTIME_OUT_STATE', 1); // 1 by default

            // Daily rows from paired sessions only
            $detailSql = "
            WITH tx AS (
                SELECT
                    t.{$trxEmpCol} AS userid,
                    t.{$trxTimeCol} AS ts
                    " . ($hasPunchState ? ", t.punch_state::int AS state" : "") . "
                FROM {$trxTable} t
                WHERE t.{$trxEmpCol} = ?
                  AND EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  " . ($hasPunchState ? "AND t.punch_state IN ('{$inState}','{$outState}')" : "") . "
            ),
            ord AS (
                SELECT
                    userid,
                    ts
                    " . ($hasPunchState ? ", state" : ", ROW_NUMBER() OVER (PARTITION BY userid ORDER BY ts) AS rn") . ",
                    LEAD(ts)    OVER (PARTITION BY userid ORDER BY ts) AS next_ts
                    " . ($hasPunchState ? ", LEAD(state) OVER (PARTITION BY userid ORDER BY ts) AS next_state" : "") . "
                FROM tx
            ),
            sessions AS (
                SELECT
                    userid,
                    ts       AS start_ts,
                    next_ts  AS end_ts,
                    EXTRACT(EPOCH FROM (next_ts - ts)) / 60.0 AS minutes
                FROM ord
                WHERE
                    next_ts IS NOT NULL
                    AND next_ts > ts
                    AND (next_ts - ts) <= INTERVAL '{$maxGapHours} hours'
                    " . ($hasPunchState
                ? "AND state = {$inState} AND next_state = {$outState}"
                : "AND (rn % 2) = 1") . "
            ),
            daily AS (
                SELECT
                    DATE(start_ts) AS punch_date,
                    MIN(start_ts)  AS first_in,
                    MAX(end_ts)    AS last_out,
                    ROUND(SUM(minutes))::int AS total_min
                FROM sessions
                GROUP BY DATE(start_ts)
            )
            SELECT
                punch_date,
                TO_CHAR(first_in, 'HH24:MI:SS') AS punch_in,
                TO_CHAR(last_out, 'HH24:MI:SS') AS punch_out,
                LPAD((total_min / 60)::text, 2, '0') || ':' || LPAD((total_min % 60)::text, 2, '0') AS total_hours,
                CASE
                    WHEN total_min > 480 THEN
                        LPAD(((total_min - 480) / 60)::text, 2, '0') || ':' ||
                        LPAD(((total_min - 480) % 60)::text, 2, '0')
                    ELSE '00:00'
                END AS overtime_hours
            FROM daily
            ORDER BY punch_date
        ";

            $detailRows = $this->retryQuery(
                fn() => \Illuminate\Support\Facades\DB::connection('bio')->select($detailSql, [$userId, $month, $year])
            );

            // Month total (paired sessions only)
            $monthTotalSql = "
            WITH tx AS (
                SELECT
                    t.{$trxEmpCol} AS userid,
                    t.{$trxTimeCol} AS ts
                    " . ($hasPunchState ? ", t.punch_state::int AS state" : "") . "
                FROM {$trxTable} t
                WHERE t.{$trxEmpCol} = ?
                  AND EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  " . ($hasPunchState ? "AND t.punch_state IN ('{$inState}','{$outState}')" : "") . "
            ),
            ord AS (
                SELECT
                    userid,
                    ts
                    " . ($hasPunchState ? ", state" : ", ROW_NUMBER() OVER (PARTITION BY userid ORDER BY ts) AS rn") . ",
                    LEAD(ts)    OVER (PARTITION BY userid ORDER BY ts) AS next_ts
                    " . ($hasPunchState ? ", LEAD(state) OVER (PARTITION BY userid ORDER BY ts) AS next_state" : "") . "
                FROM tx
            ),
            sessions AS (
                SELECT
                    userid,
                    ts       AS start_ts,
                    next_ts  AS end_ts,
                    EXTRACT(EPOCH FROM (next_ts - ts)) / 60.0 AS minutes
                FROM ord
                WHERE
                    next_ts IS NOT NULL
                    AND next_ts > ts
                    AND (next_ts - ts) <= INTERVAL '{$maxGapHours} hours'
                    " . ($hasPunchState
                ? "AND state = {$inState} AND next_state = {$outState}"
                : "AND (rn % 2) = 1") . "
            )
            SELECT
                LPAD((ROUND(COALESCE(SUM(minutes),0))::int / 60)::text, 2, '0') || ':' ||
                LPAD((ROUND(COALESCE(SUM(minutes),0))::int % 60)::text, 2, '0') AS total_month_hours
            FROM sessions
        ";

            $monthTotal = $this->retryQuery(
                fn() => \Illuminate\Support\Facades\DB::connection('bio')->selectOne($monthTotalSql, [$userId, $month, $year])
            );
            $totalMonthHours = $monthTotal?->total_month_hours ?? '00:00';

            return view('biotime.show', [
                'user'             => $userRow,
                'records'          => $detailRows,
                'days'             => $days,
                'month'            => $month,
                'year'             => $year,
                'totalMonthHours'  => $totalMonthHours,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = method_exists($this, 'getDetailedErrorMessage')
                ? $this->getDetailedErrorMessage($e)
                : $e->getMessage();

            \Log::error('BioTime query failed (show)', ['error' => $e->getMessage(), 'details' => $errorMessage]);
            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            $errorMessage = 'An unexpected error occurred: ' . $e->getMessage();
            \Log::error('Unexpected error in BioUserController::show', ['error' => $e->getMessage()]);
            return back()->with('error', $errorMessage);
        }
    }

    private function retryQuery(callable $callback, int $attempts = 3, int $delay = 1000)
    {
        $lastException = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                return $callback();
            } catch (QueryException $e) {
                $lastException = $e;
                if ($i < $attempts - 1) {
                    usleep($delay * 1000 * pow(2, $i)); // Exponential backoff
                }
            }
        }

        throw $lastException;
    }


    private function getDetailedErrorMessage(QueryException $e): string
    {
        $message = $e->getMessage();
        if (stripos($message, 'access denied') !== false) {
            return 'Failed to query BioTime server: Invalid database credentials. Please check the username and password.';
        } elseif (stripos($message, 'unknown database') !== false) {
            return 'Failed to query BioTime server: Database not found. Please verify the database name in your configuration.';
        } elseif (stripos($message, 'connection refused') !== false || stripos($message, 'could not connect') !== false) {
            return 'Failed to query BioTime server: Database server is unreachable. Please check the host, port, or network connectivity.';
        } elseif (stripos($message, 'table not found') !== false || stripos($message, 'relation does not exist') !== false) {
            return 'Failed to query BioTime server: Required tables (personnel_employee or iclock_transaction) not found in the database.';
        } elseif (stripos($message, 'column') !== false && stripos($message, 'does not exist') !== false) {
            return 'Failed to query BioTime server: One or more required columns (e.g., emp_code, first_name, last_name, punch_time, punch_state) not found in the tables.';
        } elseif (stripos($message, 'permission denied') !== false) {
            return 'Failed to query BioTime server: Database user lacks permissions to access the required tables or columns.';
        } elseif (stripos($message, 'syntax error') !== false) {
            return 'Failed to query BioTime server: Invalid SQL query syntax. Please contact support to resolve this issue.';
        } else {
            return 'Failed to query BioTime server: ' . $message . '. Please contact support if this persists.';
        }
    }

    public function details(Request $request)
    {

        try {
            // Require login + employee code
            if (!\Auth::check()) {
                return redirect()->route('login')->with('error', 'You must be logged in to view BioTime details.');
            }
            $authUser = \Auth::user();
            if (empty($authUser->ccbrt_code)) {
                return back()->with('error', 'You do not have a CCBRT code assigned.');
            }
            $empCodeValue = $authUser->ccbrt_code;

            // Inputs
            $month = (int) $request->input('month', (int) date('m'));
            $year  = (int) $request->input('year',  (int) date('Y'));
            if ($month < 1 || $month > 12)  throw new \Exception('Invalid month value.');
            if ($year  < 2000 || $year  > (int) date('Y')) throw new \Exception('Invalid year value.');

            // If you set session timezone for the bio connection, do it
            if (method_exists($this, 'setBioSession')) {
                $this->setBioSession();
            }

            // Tables
            $empTable  = 'personnel_employee';
            $deptTable = 'personnel_department';
            $trxTable  = 'iclock_transaction';

            // Helper to pick a present column
            $pick = function (string $table, array $candidates) {
                foreach ($candidates as $c) {
                    if (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($table, $c)) return $c;
                }
                return null;
            };

            // Employee-code column on employee table
            $empCodeColEmp = $pick($empTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
            if (!$empCodeColEmp) {
                throw new \Exception("Could not find an employee code column on {$empTable}.");
            }

            // "name" expression
            $hasFirst = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'first_name');
            $hasLast  = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'last_name');
            if ($hasFirst && $hasLast) {
                $nameExpr = "COALESCE(e.first_name,'') || ' ' || COALESCE(e.last_name,'')";
            } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'name')) {
                $nameExpr = "COALESCE(e.name,'Unknown')";
            } elseif (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($empTable, 'full_name')) {
                $nameExpr = "COALESCE(e.full_name,'Unknown')";
            } else {
                $nameExpr = "COALESCE(e.{$empCodeColEmp}::text,'Unknown')";
            }

            // (Optional) department join
            $empDeptIdCol   = $pick($empTable, ['dept_id', 'department_id', 'deptid']);
            $empDeptCodeCol = $pick($empTable, ['dept_code', 'department_code']);
            $deptIdCol      = $pick($deptTable, ['id']);
            $deptCodeCol    = $pick($deptTable, ['dept_code']);
            $deptNameCol    = $pick($deptTable, ['dept_name', 'name']);

            if ($deptNameCol && $empDeptIdCol && $deptIdCol) {
                $userJoin = "LEFT JOIN {$deptTable} d ON d.{$deptIdCol} = e.{$empDeptIdCol}";
                $deptSel  = "COALESCE(d.{$deptNameCol}, 'Unknown') AS department";
            } elseif ($deptNameCol && $empDeptCodeCol && $deptCodeCol) {
                $userJoin = "LEFT JOIN {$deptTable} d ON d.{$deptCodeCol} = e.{$empDeptCodeCol}";
                $deptSel  = "COALESCE(d.{$deptNameCol}, 'Unknown') AS department";
            } else {
                $userJoin = "";
                $deptSel  = "'Unknown' AS department";
            }

            // Transactions cols
            $trxTimeCol = $pick($trxTable, ['punch_time', 'checktime', 'log_time', 'time']);
            if (!$trxTimeCol) {
                throw new \Exception("Could not find punch time column on {$trxTable}.");
            }
            $trxEmpCol = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, $empCodeColEmp)
                ? $empCodeColEmp
                : $pick($trxTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']);
            if (!$trxEmpCol) throw new \Exception("Could not find employee reference column on {$trxTable}.");

            $hasPunchState = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, 'punch_state');
            $stateClause   = $hasPunchState ? "AND t.punch_state IN ('0','1')" : "";

            // Direction mapping (swap strings here if your devices are reversed)
            $directionExpr = $hasPunchState
                ? "CASE WHEN t.punch_state='0' THEN 'Check In' WHEN t.punch_state='1' THEN 'Check Out' ELSE 'Punch' END"
                : "'Punch'";

            // --- Fetch user meta
            $userRow = $this->retryQuery(function () use ($empTable, $userJoin, $deptSel, $empCodeColEmp, $nameExpr, $empCodeValue) {
                $sql = "
                SELECT e.{$empCodeColEmp} AS userid, {$nameExpr} AS name, {$deptSel}
                FROM {$empTable} e
                {$userJoin}
                WHERE e.{$empCodeColEmp} = ?
                LIMIT 1
            ";
                $rows = \Illuminate\Support\Facades\DB::connection('bio')->select($sql, [$empCodeValue]);
                return $rows ? $rows[0] : (object)[
                    'userid'     => $empCodeValue,
                    'name'       => 'Unknown',
                    'department' => 'Unknown',
                ];
            });

            // --- Raw transactions for month (for expand & modal)
            $rawRows = $this->retryQuery(function () use ($trxTable, $trxEmpCol, $trxTimeCol, $stateClause, $directionExpr, $empCodeValue, $month, $year) {
                $sql = "
                SELECT
                    DATE(t.{$trxTimeCol})                          AS punch_date,
                    TO_CHAR(t.{$trxTimeCol}, 'HH24:MI:SS')         AS punch_time,
                    {$directionExpr}                                AS direction
                FROM {$trxTable} t
                WHERE t.{$trxEmpCol} = ?
                  AND EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                  AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                  {$stateClause}
                ORDER BY t.{$trxTimeCol} ASC
            ";
                return \Illuminate\Support\Facades\DB::connection('bio')->select($sql, [$empCodeValue, $month, $year]);
            });

            // --- Daily first/last for month (with formatted STRING + full DT string)
            $detailRows = $this->retryQuery(function () use ($trxTable, $trxEmpCol, $trxTimeCol, $stateClause, $empCodeValue, $month, $year) {
                $sql = "
                WITH daily AS (
                    SELECT
                        t.{$trxEmpCol}       AS userid,
                        DATE(t.{$trxTimeCol}) AS punch_date,
                        MIN(t.{$trxTimeCol})  AS first_punch,
                        MAX(t.{$trxTimeCol})  AS last_punch
                    FROM {$trxTable} t
                    WHERE t.{$trxEmpCol} = ?
                      AND EXTRACT(MONTH FROM t.{$trxTimeCol}) = ?
                      AND EXTRACT(YEAR  FROM t.{$trxTimeCol}) = ?
                      {$stateClause}
                    GROUP BY t.{$trxEmpCol}, DATE(t.{$trxTimeCol})
                )
                SELECT
                    userid,
                    punch_date,
                    TO_CHAR(first_punch, 'HH24:MI:SS')          AS punch_in,
                    TO_CHAR(last_punch,  'HH24:MI:SS')          AS punch_out,
                    TO_CHAR(first_punch, 'YYYY-MM-DD HH24:MI:SS') AS punch_in_dt,
                    TO_CHAR(last_punch,  'YYYY-MM-DD HH24:MI:SS') AS punch_out_dt
                FROM daily
                ORDER BY punch_date
            ";
                return \Illuminate\Support\Facades\DB::connection('bio')->select($sql, [$empCodeValue, $month, $year]);
            });

            return view('biotime.user', [
                'user'        => $userRow,
                'records'     => $detailRows,   // daily rows
                'rawRecords'  => $rawRows,      // full month transactions
                'month'       => $month,
                'year'        => $year,
            ]);
        } catch (QueryException $e) {
            $errorMessage = method_exists($this, 'getDetailedErrorMessage')
                ? $this->getDetailedErrorMessage($e)
                : $e->getMessage();
            \Log::error('BioTime query failed in details', ['error' => $e->getMessage(), 'details' => $errorMessage]);
            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            $errorMessage = 'An unexpected error occurred: ' . $e->getMessage();
            \Log::error('Unexpected error in BioUserController::details', ['error' => $e->getMessage()]);
            return back()->with('error', $errorMessage);
        }
    }
}
