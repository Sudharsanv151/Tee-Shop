<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "online_shop";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch cart details
$sql = "SELECT SUM(CAST(REPLACE(price, '₹', '') AS DECIMAL(10, 2))) as total_price, COUNT(*) as num_items FROM cart";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_price = $row['total_price'];
    $num_items = $row['num_items'];
} else {
    $total_price = 0;
    $num_items = 0;
}

$total_amount = number_format($total_price, 2);

$invoice_html = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $payment_method = $_POST['payment'];
    $screenshot_path = null;

    // Handle file upload if Google Pay is selected
    if ($payment_method == 'gpay' && isset($_FILES['payment_screenshot'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["payment_screenshot"]["name"]);
        
        if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $target_file)) {
            $screenshot_path = $target_file;
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }

    // Insert order into database
    $stmt = $conn->prepare("INSERT INTO orders (name, phone, address, email, total_price, num_items, payment_method, screenshot_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("ssssdiis", $name, $phone, $address, $email, $total_price, $num_items, $payment_method, $screenshot_path);

    if ($stmt->execute()) {
        $order_id = $conn->insert_id; // Get the ID of the inserted order
        $invoice_html = generateInvoice($order_id, $name, $phone, $address, $email, $total_price, $num_items, $payment_method);
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();

function generateInvoice($order_id, $name, $phone, $address, $email, $total_price, $num_items, $payment_method) {
    $invoice_date = date("Y-m-d H:i:s");
    $invoice_html = "
    <div class='invoice'>
        <h1>Invoice</h1>
        <table>
            <tr><th>Order ID:</th><td>$order_id</td></tr>
            <tr><th>Date:</th><td>$invoice_date</td></tr>
            <tr><th>Name:</th><td>$name</td></tr>
            <tr><th>Phone:</th><td>$phone</td></tr>
            <tr><th>Address:</th><td>$address</td></tr>
            <tr><th>Email:</th><td>$email</td></tr>
            <tr><th>Number of Items:</th><td>$num_items</td></tr>
            <tr><th>Total Amount:</th><td>₹" . number_format($total_price, 2) . "</td></tr>
            <tr><th>Payment Method:</th><td>" . ucfirst($payment_method) . "</td></tr>
        </table>
        <button onclick='window.print()' class='submit-btn'>Print Invoice</button>
    </div>";
    return $invoice_html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Summary and Payment</title>
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
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .contain {
            width: 80%;
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
        }
        .payment-methods {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-top: 20px;
        }
        .payment-method {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .payment-method input[type="radio"] {
            width: auto;
            margin-right: 10px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .qr-code {
            width: 100px;
            height: auto;
            margin-top: 10px;
        }
        #gpay-button {
            margin-top: 10px;
        }
        .invoice {
            background: white;
            color: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .invoice table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .invoice th, .invoice td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .invoice th {
            font-weight: bold;
            width: 40%;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .invoice, .invoice * {
                visibility: visible;
            }
            .invoice {
                position: absolute;
                left: 0;
                top: 0;
            }
            .submit-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div id="order-form" class="contain">
        <h1>Order Summary and Payment</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <h2>Delivery Information</h2>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <textarea name="address" placeholder="Delivery Address" required></textarea>
            <input type="email" name="email" placeholder="Email Address" required>

            <h2>Order Details</h2>
            <table>
                <tr>
                    <th>Price (<?php echo $num_items;?> items)</th>
                    <td>₹<?php echo number_format($total_price, 2);?></td>
                </tr>
                <tr>
                    <th>Delivery Charges</th>
                    <td>FREE Delivery</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td>₹<?php echo $total_amount;?></td>
                </tr>
            </table>

            <h2>Payment Method</h2>
            <div class="payment-methods">
                <div class="payment-method">
                    <input type="radio" name="payment" value="cash" id="cash" required>
                    <label for="cash">Cash on Delivery</label>
                </div>
                <div class="payment-method">
                    <input type="radio" name="payment" value="gpay" id="gpay" required>
                    <label for="gpay">Google Pay</label>
                </div>
                <div id="gpay-button" style="display: none;">
                    <img id="qr-code" class="qr-code" src="pay.jpg" alt="QR Code">
                    <input type="file" id="payment-screenshot" name="payment_screenshot" accept="image/*">
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Place Order</button>
        </form>
    </div>

    <div id="invoice-container" class="contain" style="display: none;">
        <?php echo $invoice_html; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var gpayRadio = document.getElementById('gpay');
            var qrCodeContainer = document.getElementById('gpay-button');

            function toggleGpayButton() {
                qrCodeContainer.style.display = gpayRadio.checked ? 'block' : 'none';
            }

            gpayRadio.addEventListener('change', toggleGpayButton);
            document.getElementById('cash').addEventListener('change', toggleGpayButton);

            // Initial check
            toggleGpayButton();

            <?php if (!empty($invoice_html)): ?>
            document.getElementById('order-form').style.display = 'none';
            document.getElementById('invoice-container').style.display = 'block';
            <?php endif; ?>
        });
    </script>
</body>
</html>