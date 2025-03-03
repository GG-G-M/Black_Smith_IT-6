<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="row w-75 shadow-lg bg-white rounded p-4">
            <div class="col-md-6 d-flex flex-column justify-content-center">
                <h3 class="mb-3">Register</h3>
                <p class="text-muted">Skibidi Toilet</p>

                <form>
                    <div class="mb-3">
                        <label class="form-label">Full Name*</label>
                        <input type="fullname" class="form-control" placeholder="Ex: Gilgre Gene G. Mantilla" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email*</label>
                        <input type="username" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password*</label>
                        <input type="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Register</button>
                </form>

                <p class="mt-3"><a href="login.html" class="text-dark">Already have an Account? Login!</a></p>
            </div>

            <!-- Right Side: Image/Illustration -->
            <div class="col-md-6 d-none d-md-block">
                <img src="image.png" class="img-fluid" alt="Illustration">
            </div>
        </div>
    </div>

</body>
</html>
