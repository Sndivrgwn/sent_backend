<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
</head>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    color: #343a40;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

header {
    background-color: #1a202c;
    color: white;
    padding: 20px;
    text-align: center;
}

h1 {
    margin: 0;
}

h2 {
    color: #1a202c;
}

.endpoint {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin: 20px 0;
    padding: 15px;
}

pre {
    background-color: #f1f1f1;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}

footer {
    text-align: center;
    margin-top: 20px;
    font-size: 0.9em;
}

/* Responsiveness */
@media (max-width: 600px) {
    .container {
        padding: 10px;
    }

    header {
        padding: 15px;
    }

    .endpoint {
        padding: 10px;
    }

    pre {
        font-size: 14px; /* Mengurangi ukuran font untuk tampilan mobile */
    }
}
</style>

<body>
    <div class="container">
        <header>
            <h1>API Documentation</h1>
            <p>Welcome to the API documentation. Below are the available endpoints.</p>
        </header>

        <section class="endpoint">
            <h2>POST /api/messages</h2>
            <p><strong>Description:</strong> send messages</p>
            <p><strong>Request Body:</strong></p>
            <pre>
{
    "username": "Jane Doe",
    "messages": "hello there how are you",
}
            </pre>
            <p><strong>Response:</strong></p>
            <pre>
{
    success => true
}
            </pre>
        </section>

        <section class="endpoint">
            <h2>POST /api/register</h2>
            <p><strong>Description:</strong> Create a new user.</p>
            <p><strong>Request Body:</strong></p>
            <pre>
{
    "name": "New User",
    "email": "newuser@example.com",
    "kelas": "XII TKJ 1",
    "divisi": "programming"
}
            </pre>
            <p><strong>Response:</strong></p>
            <pre>
{
    "success": true,
    "user": {
        "name": "New User",
        "email": "newuser@example.com",
        "kelas": "XII TKJ 1",
        "divisi": "programming",
        "updated_at": "2024-12-25T23:13:36.000000Z",
        "created_at": "2024-12-25T23:13:36.000000Z",
        "id": 1
    }
}
            </pre>
        </section>

        <section class="endpoint">
            <h2>POST /api/login</h2>
            <p><strong>Description:</strong> Login into application</p>
            {
    "email": "newuser@example.com",
    "password": "bigboy123",
}
            <p><strong>Response:</strong></p>
            <pre>
{
    {
    "success": true,
    "user": {
        "id": 1,
        "name": "New User",
        "role": "user",
        "email": "newuser@example.com",
        "kelas": XII TKJ 1,
        "divisi": Programming,
        "no_hp": null,
        "email_verified_at": null,
        "created_at": "2024-12-13T23:01:56.000000Z",
        "updated_at": "2024-12-13T23:01:56.000000Z"
    },
    "token": "token1"
}
}
            </pre>
        </section>

                <section class="endpoint">
            <h2>POST /api/logout</h2>
            <p><strong>Description:</strong> make user leave application</p>
            <pre>
{
    "token": "token"
}
            </pre>
            <p><strong>Response:</strong></p>
            <pre>
{
    "success": true,
    "message": "Logout Berhasil!"
}
            </pre>
        </section>

        <footer>
            <p>&copy; 2024 Sent. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>