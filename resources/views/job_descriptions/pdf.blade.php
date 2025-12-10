<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Job Description</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #a7cf3a;
            font-weight: bold;
        }

        .section-header {
            background-color: #006400;
            color: #fff;
            padding: 10px;
            font-weight: bold;
            font-size: 14pt;
        }

        .subsection-title {
            background-color: #a7cf3a;
            color: #000;
            padding: 8px;
            font-weight: bold;
            font-size: 12pt;
        }

        .logo {
            width: 70px;
            float: right;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 6px;
        }

        .footer {
            font-size: 10pt;
            text-align: right;
            margin-top: 20px;
        }

        @page {
            size: A4;
            margin: 2cm;
        }

        .page-break {
            page-break-before: always;
        }

        .header-box {
            text-align: center;
        }

        .header-title {
            font-weight: bold;
            font-size: 18pt;
        }

        .highlight-box {
            background-color: yellow;
            display: inline-block;
            padding: 2px 6px;
            font-weight: bold;
            margin-top: 6px;
        }

        .table-header-full {
            background-color: #006400;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 14pt;
        }
    </style>
</head>

<body>
    <div class="header-box">
        <img src="{{ public_path('assets/img/ccbrt.JPG') }}" class="logo" alt="Logo">
        <div class="header-title">JOB DESCRIPTION</div>
        <div class="highlight-box">(TITLE)</div>
    </div>

    <table>
        <tr>
            <td colspan="2" class="table-header-full">JOB DETAILS</td>
        </tr>
        <tr>
            <th>OPERATIONAL JOB TITLE</th>
            <td>{{ $jobDescription->job_title ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>TECHNICAL JOB LEVEL</th>
            <td>{{ $jobDescription->technical_job_level ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>REPORTS TO</th>
            <td>{{ $jobDescription->reports_to ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>JOBS RESPONSIBLE FOR</th>
            <td>{{ $jobDescription->jobs_responsible_for ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>DEPARTMENT</th>
            <td>{{ $jobDescription->user && $jobDescription->user->department ? $jobDescription->user->department->dept_name : 'N/A' }}
            </td>
        </tr>
        <tr>
            <th>REGION/LOCATION</th>
            <td>{{ $jobDescription->region_location ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>WORKING HOURS</th>
            <td>{{ $jobDescription->working_hours ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>JOB REVIEW DATE</th>
            <td>{{ $jobDescription->job_review_date ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>JOB GRADE</th>
            <td>{{ $jobDescription->job_grade ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>NAME JOB HOLDER</th>
            <td>{{ $jobDescription->user ? $jobDescription->user->fname . ' ' . ($jobDescription->user->lname ?? '') : 'N/A' }}
            </td>
        </tr>
        <tr>
            <th>GRADE JOB HOLDER</th>
            <td>{{ $jobDescription->grade_job_holder ?? 'N/A' }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Reasoning for difference between grade job holder and job grade (if any)</th>
            <td>{{ $jobDescription->grade_difference_reason ?? 'N/A' }}</td>
        </tr>
    </table>


    <div class="section-header">A. OUTPUTS</div>
    <div class="subsection-title">PURPOSE</div>
    <table>
        <tr>
            <td>{!! $jobDescription->purpose ?? 'N/A' !!}</td>
        </tr>
    </table>
    <div class="page-break"></div>

    <div class="subsection-title">ACCOUNTABILITIES / KEY OUTPUTS</div>
    <table>
        <tr>
            <td>{!! $jobDescription->accountabilities ?? 'N/A' !!}</td>
        </tr>
    </table>

    <div class="section-header">B. INPUTS</div>
    <div class="subsection-title">KEY QUALIFICATIONS, EXPERIENCE & SKILLS</div>
    <table>
        <tr>
            <td>{!! $jobDescription->qualifications_experience ?? 'N/A' !!}</td>
        </tr>
    </table>

    <div class="subsection-title">COMPETENCIES</div>
    <table>
        <tr>
            <td>{!! $jobDescription->competencies ?? 'N/A' !!}</td>
        </tr>
    </table>


    <div class="section-header">OTHER DIMENSIONS (if applicable)</div>
    <table>
        <tr>
            <th>FINANCIAL (e.g. Budget, turnover, expenses, assets, profit)</th>
            <td>{!! $jobDescription->financial_details ?? 'N/A' !!}</td>
        </tr>
        <tr>
            <th>EMPLOYEES MANAGED (direct/indirect)</th>
            <td>{!! $jobDescription->employees_managed ?? 'N/A' !!}</td>
        </tr>
        <tr>
            <th>STAKEHOLDERS MANAGED</th>
            <td>{!! $jobDescription->stakeholders_managed ?? 'N/A' !!}</td>
        </tr>
    </table>
    <div class="page-break"></div>

    <div class="section-header">ORGANISATION / DEPARTMENTAL STRUCTURE</div>
    <table>
        <tr>
            <td>
                <div class="checkbox {{ $jobDescription->organisation_structure ? 'filled' : '' }}"></div> Yes
                <div class="checkbox {{ !$jobDescription->organisation_structure ? 'filled' : '' }}"></div> No
            </td>
        </tr>
    </table>

    <div class="section-header">AUTHORISATION DETAILS</div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Signature</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Job Profile confirmed (HR):</td>
                <td>{{ $jobDescription->authorisation_details['hr']['signature'] ?? 'N/A' }}</td>
                <td>{{ $jobDescription->authorisation_details['hr']['date'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Employee:</td>
                <td>{{ $jobDescription->authorisation_details['employee']['signature'] ?? 'N/A' }}</td>
                <td>{{ $jobDescription->authorisation_details['employee']['date'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Line Manager:</td>
                <td>{{ $jobDescription->authorisation_details['line_manager']['signature'] ?? 'N/A' }}</td>
                <td>{{ $jobDescription->authorisation_details['line_manager']['date'] ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">CCBRT HR</div>
</body>

</html>
