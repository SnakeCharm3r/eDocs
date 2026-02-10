<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something Went Wrong</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f9f9f9;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            color: #333;
        }

        .container {
            max-width: 450px;
        }

        h1 {
            font-size: 100px;
            color: #4caf50;
            margin: 0;
        }

        p {
            font-size: 20px;
            margin: 20px 0;
        }

        a {
            display: inline-block;
            padding: 12px 25px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        a:hover {
            background: #45a049;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>500</h1>
        <p>Oops! Something went wrong on our end. Please try again later.</p>
        <a href="{{ url('/') }}">Go Back Home</a>
    </div>
</body>

</html>
