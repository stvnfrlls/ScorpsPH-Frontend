<?php
session_start();
require_once "dbconn.php";
$errors = array();

function validate($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

//login button clicked
if (isset($_POST["login"])) {
    $email = validate(mysqli_real_escape_string($conn, $_POST["email"]));
    $password = validate(mysqli_real_escape_string($conn, $_POST["password"]));

    $check_email = "SELECT * FROM customers WHERE user_email = '$email'";
    $res = mysqli_query($conn, $check_email);
    if (mysqli_num_rows($res) > 0) {
        $fetch = mysqli_fetch_assoc($res);
        $fetch_pass = $fetch['user_password'];
        if (password_verify($password, $fetch_pass)) {

            $_SESSION['email'] = $email;
            $status = $fetch['user_verified'];
            $account_level = $fetch['account_level'];
            $unique_id = $fetch['user_unique_id'];
            if ($status == 'verified' && $account_level !== "ADMIN") {
                $_SESSION['email'] = $email;
                $_SESSION['unique_id'] = $unique_id;
                header('location: ndex.php');
                die();
            } elseif ($account_level === "ADMIN") {
                $_SESSION['unique_id'] = $unique_id;
                header('location: admin.php');
                die();
            } else {
                $info = "It's look like you haven't still verify your email - $email";
                $_SESSION['info'] = $info;
                header('location: otp.php');
                die();
            }
        } else {
            $errors['email'] = "Incorrect email or password!";
            die();
        }
    } else {
        $errors['email'] = "It's look like you're not yet a member! Click on the bottom link to signup.";
        die();
    }
}
//end of login function

//sign up button clicked
if (isset($_POST["signup"])) {
    $lname = validate(mysqli_real_escape_string($conn, $_POST["lname"]));
    $fname = validate(mysqli_real_escape_string($conn, $_POST["fname"]));

    $email = validate(mysqli_real_escape_string($conn, $_POST["email"]));
    $password = validate(mysqli_real_escape_string($conn, $_POST["password"]));
    $c_password = validate(mysqli_real_escape_string($conn, $_POST["cpassword"]));

    if ($password !== $c_password) {
        $errors['password'] = "Confirm password not matched!";
    } else {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Your email is Invalid.";
        } else {
            $check_email = "SELECT * FROM customers WHERE user_email = '$email'";
            $result = mysqli_query($conn, $check_email);

            if (mysqli_num_rows($result) > 0) {
                $errors['email'] = "Email that you have entered is already exist!";
            }
            if (count($errors) === 0) {
                $encpass = password_hash($password, PASSWORD_BCRYPT);
                $otp = rand(999999, 111111);
                $uni_id = rand(time(), 99999);
                $status = "notverified";
                $insert_data = "INSERT INTO customers 
                                        (user_unique_id, user_lname, user_fname, user_email, 
                                         user_verified, user_otp, user_password)
                                        values
                                        ('$uni_id','$lname', '$fname', '$email', 
                                         '$status', '$otp', '$encpass')";

                $data_check = mysqli_query($conn, $insert_data);
                if ($data_check) {
                    $subject = "Email Verification Code";
                    $message = "Your verification code is $otp";
                    $sender = "From: SCORPSPH";
                    if (mail($email, $subject, $message, $sender)) {
                        $info = "We've sent a verification code to your email - $email";
                        $_SESSION['info'] = $info;
                        $_SESSION['email'] = $email;
                        $_SESSION['password'] = $password;
                        header('location: otp.php');
                    } else {
                        $errors['otp-error'] = "Failed while sending code!";
                    }
                } else {
                    $errors['signup-error'] = "Failed while inserting data into database!";
                }
            }
        }
    }
}
//end of sign up process

//submit button in OTP page clicked
if (isset($_POST['verify-account'])) {
    $_SESSION['info'] = "";
    $otp_code = mysqli_real_escape_string($conn, $_POST['otp']);
    $check_code = "SELECT * FROM customers WHERE user_otp = $otp_code";
    $code_res = mysqli_query($conn, $check_code);
    if (mysqli_num_rows($code_res) > 0) {
        $fetch_data = mysqli_fetch_assoc($code_res);
        $fetch_code = $fetch_data['user_otp'];
        $email = $fetch_data['user_email'];
        $code = 0;
        $status = 'verified';
        $update_otp = "UPDATE customers SET user_otp = $code, user_verified = '$status' WHERE user_otp = $fetch_code";
        $update_res = mysqli_query($conn, $update_otp);
        if ($update_res) {
            $_SESSION['email'] = $email;
            header('location: index.php');
            exit();
        } else {
            $errors['otp-error'] = "Failed while updating code!";
        }
    } else {
        $errors['otp-error'] = "You've entered incorrect code!";
    }
}
//end of OTP function

//change pass
//verify email
if (isset($_POST['verify-email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $check_email = "SELECT * FROM customers WHERE user_email='$email'";
    $run_sql = mysqli_query($conn, $check_email);
    if (mysqli_num_rows($run_sql) > 0) {
        $code = rand(999999, 111111);
        $insert_code = "UPDATE customers SET user_otp = $code WHERE user_email = '$email'";
        $run_query =  mysqli_query($conn, $insert_code);
        if ($run_query) {
            $subject = "Password Reset Code";
            $message = "Your password reset code is $code";
            $sender = "From: SCORPSPH";
            if (mail($email, $subject, $message, $sender)) {
                $info = "We've sent a password reset otp to your email - $email";
                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email;
                header('location: otp-password.php');
                exit();
            } else {
                $errors['otp-error'] = "Failed while sending code!";
            }
        } else {
            $errors['db-error'] = "Something went wrong!";
        }
    } else {
        $errors['email'] = "This email address does not exist!";
    }
}
//end page

//change pass
if (isset($_POST['verify-otp'])) {
    $_SESSION['info'] = "";
    $otp_code = mysqli_real_escape_string($conn, $_POST['otp']);
    $check_code = "SELECT * FROM customers WHERE user_otp = $otp_code";
    $code_res = mysqli_query($conn, $check_code);
    if (mysqli_num_rows($code_res) > 0) {
        $fetch_data = mysqli_fetch_assoc($code_res);
        $email = $fetch_data['email'];
        $_SESSION['email'] = $email;
        $info = "Please create a new password that you don't use on any other site.";
        $_SESSION['info'] = $info;
        header('location: new-password.php');
        exit();
    } else {
        $errors['otp-error'] = "You've entered incorrect code!";
    }
}
//end page

//update pass
if (isset($_POST['password'])) {
    $_SESSION['info'] = "";
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $cpassword = mysqli_real_escape_string($conn, $_POST['cpassword']);
    if ($password !== $cpassword) {
        $errors['password'] = "Confirm password not matched!";
    } else {
        $code = 0;
        $email = $_SESSION['email']; //getting this email using session
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $update_pass = "UPDATE customers SET user_otp = $code, user_password = '$encpass' WHERE user_email = '$email'";
        $run_query = mysqli_query($conn, $update_pass);
        if ($run_query) {
            $info = "Your password changed. Now you can login with your new password.";
            $_SESSION['info'] = $info;
            header('Location: redirect-to-login.php');
        } else {
            $errors['db-error'] = "Failed to change your password!";
        }
    }
}
//end page

//redirect to login page
if (isset($_POST['login-now'])) {
    header('location: entry.php');
}
//end page
//end change

//shop functions
//add to cart
if (isset($_POST['addtocart']) && !empty($_SESSION['email'])) {
    $prodname = mysqli_escape_string($conn, $_POST['prod_name']);
    $prodprice = mysqli_escape_string($conn, $_POST['prod_price']);
    $prodqty = mysqli_escape_string($conn, $_POST['qty']);

    $check_cart = "SELECT * FROM cart WHERE email = '{$_SESSION['email']}' AND product_name = '$prodname'";
    $run_query = mysqli_query($conn, $check_cart);

    if (mysqli_num_rows($run_query) > 0) {
        $get_itemdata = mysqli_fetch_assoc($run_query);
        $get_itemqty = $get_itemdata['quantity'];
        $updt_qty = $get_itemqty + $prodqty;

        if ($get_itemqty >= 1) {
            $update_cart = "UPDATE cart 
                                        SET quantity = '$updt_qty' 
                                        WHERE email = '{$_SESSION['email']}' AND product_name = '$prodname'";
            $run_query_update = mysqli_query($conn, $update_cart);
            if ($run_query_update) {
                header('location: cart.php');
            } else {
                echo "error update";
            }
        } else {
            echo "error inserting/updating cart data";
        }
    } elseif (mysqli_num_rows($run_query) == 0) {
        $add_tocart = "INSERT INTO cart(email, product_name, quantity, price)
                                   VALUES('{$_SESSION['email']}', '$prodname', '$prodqty', '$prodprice')";
        $run_query_insert = mysqli_query($conn, $add_tocart);
        if ($run_query_insert) {
            header('location: cart.php');
        }
    }
}
//end

//buy now
if (isset($_POST['buynow']) && !empty($_SESSION['email'])) {
    $prodname = mysqli_escape_string($conn, $_POST['prod_name']);
    $prodprice = mysqli_escape_string($conn, $_POST['prod_price']);
    $prodqty = mysqli_escape_string($conn, $_POST['qty']);

    $add_tocheckout = "INSERT INTO checkout(email, product_name, quantity, price)
                                   VALUES('{$_SESSION['email']}', '$prodname', '$prodqty', '$prodprice')";
    $run_query_insert = mysqli_query($conn, $add_tocheckout);
    if ($run_query_insert) {
        header('location: checkout-form.php');
    }
}
//end

//remove item from cart
if (isset($_POST['remove_item']) && !empty($_SESSION['email'])) {
    $prod_name = mysqli_escape_string($conn, $_POST['hidden_name']);

    $delete_item_from_cart = "DELETE FROM cart WHERE product_name= '$prod_name' AND email = '{$_SESSION['email']}'";
    $res_delete_item_from_cart = mysqli_query($conn, $delete_item_from_cart);
    if ($res_delete_item_from_cart) {
        echo "item deleted";
        header('location: cart.php');
    }
}
//end

//remove item from checkout
if (isset($_POST['remove_checkout']) && !empty($_SESSION['email'])) {
    $prod_name = mysqli_escape_string($conn, $_POST['prod_name']);

    $delete_item_from_cart = "DELETE FROM checkout WHERE product_name= '$prod_name' AND email = '{$_SESSION['email']}'";
    $res_delete_item_from_cart = mysqli_query($conn, $delete_item_from_cart);
    if ($res_delete_item_from_cart) {
        echo "item deleted";
        header('location: checkout-form.php');
    }
}
//end
//end functions

//checkout-form
if (isset($_POST['gcash_pmthd']) && !empty($_SESSION['email'])) {
    $cf_fname    = validate(mysqli_real_escape_string($conn, $_POST["fname"]));
    $cf_lname    = validate(mysqli_real_escape_string($conn, $_POST["lname"]));
    $cf_address  = validate(mysqli_real_escape_string($conn, $_POST["address"]));
    $cf_barangay = validate(mysqli_real_escape_string($conn, $_POST["barangay"]));
    $cf_city     = validate(mysqli_real_escape_string($conn, $_POST["city"]));
    $cf_province = validate(mysqli_real_escape_string($conn, $_POST["province"]));
    $cf_region   = validate(mysqli_real_escape_string($conn, $_POST["region"]));
    $cf_postal   = validate(mysqli_real_escape_string($conn, $_POST["postal"]));
    $cf_email    = validate(mysqli_real_escape_string($conn, $_POST["email"]));
    $cf_phone    = validate(mysqli_real_escape_string($conn, $_POST["phone"]));

    $diff_fname    = validate(mysqli_real_escape_string($conn, $_POST["diff_fname"]));
    $diff_lname    = validate(mysqli_real_escape_string($conn, $_POST["diff_lname"]));
    $diff_address  = validate(mysqli_real_escape_string($conn, $_POST["diff_address"]));
    $diff_barangay = validate(mysqli_real_escape_string($conn, $_POST["diff_barangay"]));
    $diff_city     = validate(mysqli_real_escape_string($conn, $_POST["diff_city"]));
    $diff_province = validate(mysqli_real_escape_string($conn, $_POST["diff_province"]));
    $diff_region   = validate(mysqli_real_escape_string($conn, $_POST["diff_region"]));
    $diff_postal   = validate(mysqli_real_escape_string($conn, $_POST["diff_postal"]));
    $diff_email    = validate(mysqli_real_escape_string($conn, $_POST["diff_email"]));
    $diff_phone    = validate(mysqli_real_escape_string($conn, $_POST["diff_phone"]));
}

if (isset($_POST['BDO_pmthd']) && !empty($_SESSION['email'])) {
    $cf_fname    = validate(mysqli_real_escape_string($conn, $_POST["fname"]));
    $cf_lname    = validate(mysqli_real_escape_string($conn, $_POST["lname"]));
    $cf_address  = validate(mysqli_real_escape_string($conn, $_POST["address"]));
    $cf_barangay = validate(mysqli_real_escape_string($conn, $_POST["barangay"]));
    $cf_city     = validate(mysqli_real_escape_string($conn, $_POST["city"]));
    $cf_province = validate(mysqli_real_escape_string($conn, $_POST["province"]));
    $cf_region   = validate(mysqli_real_escape_string($conn, $_POST["region"]));
    $cf_postal   = validate(mysqli_real_escape_string($conn, $_POST["postal"]));
    $cf_email    = validate(mysqli_real_escape_string($conn, $_POST["email"]));
    $cf_phone    = validate(mysqli_real_escape_string($conn, $_POST["phone"]));

    $diff_fname    = validate(mysqli_real_escape_string($conn, $_POST["diff_fname"]));
    $diff_lname    = validate(mysqli_real_escape_string($conn, $_POST["diff_lname"]));
    $diff_address  = validate(mysqli_real_escape_string($conn, $_POST["diff_address"]));
    $diff_barangay = validate(mysqli_real_escape_string($conn, $_POST["diff_barangay"]));
    $diff_city     = validate(mysqli_real_escape_string($conn, $_POST["diff_city"]));
    $diff_province = validate(mysqli_real_escape_string($conn, $_POST["diff_province"]));
    $diff_region   = validate(mysqli_real_escape_string($conn, $_POST["diff_region"]));
    $diff_postal   = validate(mysqli_real_escape_string($conn, $_POST["diff_postal"]));
    $diff_email    = validate(mysqli_real_escape_string($conn, $_POST["diff_email"]));
    $diff_phone    = validate(mysqli_real_escape_string($conn, $_POST["diff_phone"]));
}
    //end form
