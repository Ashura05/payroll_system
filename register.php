<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <img src="fb63b6b5-9d67-45d4-a428-51f2692f4cdb.jpeg" alt="Company Logo" class="top-logo">
            <h1 class="company-name">Noure Coffee Shop</h1>
        </div>
    </header>
    <main>
        <div class="left-side">
            <img src="fb63b6b5-9d67-45d4-a428-51f2692f4cdb.jpeg" alt="Company Logo" class="logo-left">
        </div>
        <div class="right-side">
            <div class="login-box">
                <h2>Register</h2>
                <form action="#">
                    <div class="input-group">
                        <label for="full-name">Full Name</label>
                        <input type="text" id="full-name" name="full-name" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm-password" required>
                    </div>
                    <div class="input-group">
                        <input type="submit" value="Register">
                    </div>
                </form>
                <div class="signup-link">
                    <p>Already have an account? <a href="index.html">Login</a></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
