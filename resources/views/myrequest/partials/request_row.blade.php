<tr class="{{ $form->id !== $previousRequestId ? 'request-row' : '' }}">
    @if ($loop->first)
        <td rowspan="{{ count($histories) }}">
            {{ $counter }} <!-- Display the counter value -->
        </td>
        <td rowspan="{{ count($histories) }}">
            {{ $formType }}
        </td>
    @endif
    <td>{{ $detail['role'] }}</td>
    <td>{!! $detail['status'] !!}</td>
    <td>
        @if ($detail['status'] == "<span class='badge bg-danger'>Rejected</span>")
            {{ $detail['rejectionReason'] }}
        @endif
    </td>
    @if ($loop->first)
        <td rowspan="{{ count($histories) }}">
            {{ $form->created_at }}
        </td>
        <td rowspan="{{ count($histories) }}"></td>
        <td rowspan="{{ count($histories) }}">
            <a href="{{ route('request.edit', $form->id) }}" class="btn btn-rounded btn-outline-info">Modify</a>
            <form action="{{ route('request.destroy', $form->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-rounded btn-outline-danger">Revoke</button>
            </form>
        </td>
    @endif
</tr>
