<!-- resources/views/policies/index1.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCBRT Policies</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>CCBRT Policies</h1>
        <!-- Display policies in a list -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($policies as $policy)
                    <tr>
                        <td>{{ $policy->title }}</td>
                        <td>{!! nl2br($policy->content) !!}</td>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
