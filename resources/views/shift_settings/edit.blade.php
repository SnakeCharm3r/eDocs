@extends('layouts.template')
@section('content')
<div class="page-wrapper">
  <div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h3 class="page-title">Edit Shift</h3>
      <a href="{{ route('shift-settings.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Back
      </a>
    </div>

    <div class="card">
      <form class="card-body" method="POST" action="{{ route('shift-settings.update', $shift->id) }}">
        @csrf @method('PUT')

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name',$shift->name) }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" value="{{ old('code',$shift->code) }}">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_official_duty" value="1" id="e_is_official_duty"
                     {{ old('is_official_duty',$shift->is_official_duty) ? 'checked' : '' }}>
              <label class="form-check-label" for="e_is_official_duty">Official Duty</label>
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', optional($shift->start_time)->format('H:i')) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', optional($shift->end_time)->format('H:i')) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Days / Week</label>
            <input type="number" min="1" max="7" name="days_per_week" class="form-control" value="{{ old('days_per_week',$shift->days_per_week) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Hours / Day</label>
            <input type="number" step="0.25" name="hours_per_day" class="form-control" value="{{ old('hours_per_day',$shift->hours_per_day) }}">
          </div>

          <div class="col-md-3">
            <label class="form-label">Weekly Hours (optional)</label>
            <input type="number" step="0.25" name="weekly_hours" class="form-control" value="{{ old('weekly_hours',$shift->weekly_hours) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Active?</label>
            <select name="is_active" class="form-select">
              <option value="1" {{ old('is_active',$shift->is_active) ? 'selected':'' }}>Active</option>
              <option value="0" {{ old('is_active',$shift->is_active) ? '' : 'selected' }}>Inactive</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" rows="2" class="form-control">{{ old('description',$shift->description) }}</textarea>
          </div>
        </div>

        <div class="mt-3">
          <button class="btn btn-primary">Update</button>
          <a href="{{ route('shift-settings.index') }}" class="btn btn-light">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
