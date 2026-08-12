<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Did yoy forget your password?</title>
</head>
<body>
        <h2>Hello {{ $user->name }} ❤</h2>
        <p>We received a request to reset your password. Use the code below to reset it.</p>
        <h3 style="color: red;">{{ $user->key }}</h3>
</body>
</html>