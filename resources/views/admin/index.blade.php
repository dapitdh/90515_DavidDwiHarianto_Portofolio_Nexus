<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
</head>
<body>
    <a href="{{ route('admin.logout') }}">Logout</a>
    <h1>Welcome, {{ session('nama') }}</h1>
</body>
</html>