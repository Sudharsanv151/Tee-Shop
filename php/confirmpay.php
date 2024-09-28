    <?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "online_shop";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch the sum of all prices in the cart table
    $sql = "SELECT SUM(CAST(REPLACE(price, '₹', '') AS DECIMAL(10, 2))) as total_price FROM cart";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_price = $row['total_price'];
    } else {
        $total_price = 0;
    }

    // Convert the total price to a formatted amount
    $total_amount = number_format($total_price, 2);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $total_amount = $_POST['total_amount'];
        $payment_method = $_POST['payment'];
        $screenshot_path = null;

        // Handle file upload if Google Pay is selected
        if ($payment_method == 'gpay' && isset($_FILES['payment_screenshot'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . basename($_FILES["payment_screenshot"]["name"]);
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES["payment_screenshot"]["tmp_name"]);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                echo "File is not an image.";
                $uploadOk = 0;
            }

            // Check if file already exists
            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
            }

            // Check file size
            if ($_FILES["payment_screenshot"]["size"] > 5000000) { // 5MB limit
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }

            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
            // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $target_file)) {
                    $screenshot_path = $target_file;
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
        }

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO payments (amount, payment_mode, screenshot) VALUES (?, ?, ?)");

        // Check if prepare() returned false due to an error
        if ($stmt === false) {
            die("MySQL error: " . $conn->error);
        }

        $stmt->bind_param("dss", $total_amount, $payment_method, $screenshot_path);

        if ($stmt->execute()) {
            echo "Payment recorded successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Payment</title>
        <style>
            body {
                font-family: 'Montserrat', sans-serif;
                background: url('bg.jpeg') no-repeat center center fixed;
                background-size: cover;
                color: #fff;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .contain {
                width: 80%;
                margin: auto;
                padding: 20px;
                background: rgba(0, 0, 0, 0.5);
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            .payment-methods {
                display: flex;
                justify-content: space-around;
                margin-top: 20px;
            }
            .payment-method {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .payment-method input {
                margin-top: 10px;
            }
            .submit-btn {
                display: block;
                width: 100%;
                padding: 10px;
                border: none;
                background-color: #007bff;
                color: white;
                font-size: 16px;
                text-align: center;
                cursor: pointer;
                border-radius: 4px;
                margin-top: 20px;
            }
            .qr-code {
                width: 100px; /* Adjust the width as needed */
                height: auto;
                margin-top: 10px;
            }
        </style>
        <!-- Include Google Pay API JavaScript library -->
        <script src="https://pay.google.com/gp/p/js/pay.js" onload="onGooglePayLoaded()"></script>
    </head>
    <body>
        <div class="contain">
            <h1>Payments</h1>
            <form id="payment-form" action="" method="post" enctype="multipart/form-data">
                <p>To Pay: ₹<?php echo $total_amount; ?></p>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" name="payment" value="cash" id="cash">
                        <label for="cash">Cash on Delivery</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" name="payment" value="gpay" id="gpay">
                        <label for="gpay">Google Pay</label>
                        <div id="gpay-button" style="display: none;">
                            <img id="qr-code" class="qr-code" src="pay.jpg" alt="QR Code">
                            <input type="file" id="payment-screenshot" name="payment_screenshot" accept="image/*" style="margin-top: 10px;">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                <button type="submit" id="submit-btn" class="submit-btn">Confirm Payment</button>
            </form>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var gpayRadio = document.getElementById('gpay');
                var qrCodeContainer = document.getElementById('gpay-button');
                var screenshotInput = document.getElementById('payment-screenshot');

                gpayRadio.addEventListener('change', function() {
                    if (gpayRadio.checked) {
                        qrCodeContainer.style.display = 'block';
                        screenshotInput.style.display = 'block';
                    } else {
                        qrCodeContainer.style.display = 'none';
                        screenshotInput.style.display = 'none';
                    }
                });

                var cashRadio = document.getElementById('cash');
                cashRadio.addEventListener('change', function() {
                    qrCodeContainer.style.display = 'none';
                    screenshotInput.style.display = 'none';
                });
            });
        </script>
    </body>
    </html>
