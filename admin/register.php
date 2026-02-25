<?php
session_start();
// If logged in, go to index
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone']; // Username
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Connect to MongoDB
        require_once '../db_connect_mongo.php';

        try {
            $requestData = [
                'name' => $name,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'super_admin'
            ];

            $response = callApi('/admins/register', 'POST', $requestData);

            if ($response && isset($response['success']) && $response['success'] === true) {
                $success = "Super Admin registered successfully! You can now login.";
            } else {
                $error = $response['message'] ?? "Failed to register admin. Response invalid.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Super Admin | HRMS Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;600&display=swap');

        :root {
            --red: #FF0000;
            --black: #0A0A0A;
            --gray: #1F1F1F;
        }

        body {
            background-color: var(--black);
            font-family: 'Inter', sans-serif;
            color: white;
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image:
                linear-gradient(rgba(10, 10, 10, 0.9), rgba(10, 10, 10, 0.9)),
                repeating-linear-gradient(0deg, transparent, transparent 1px, #111 1px, #111 2px);
            background-size: cover;
            padding: 20px;
        }

        .login-card {
            background: rgba(31, 31, 31, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid #333;
            border-left: 4px solid var(--red);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, transparent 50%, rgba(255, 0, 0, 0.05) 50%);
            pointer-events: none;
        }

        .glitch {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
            position: relative;
        }

        .glitch::before,
        .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--black);
        }

        .glitch::before {
            left: 2px;
            text-shadow: -1px 0 #ff00c1;
            clip: rect(44px, 450px, 56px, 0);
            animation: glitch-anim 5s infinite linear alternate-reverse;
        }

        .glitch::after {
            left: -2px;
            text-shadow: -1px 0 #00fff9;
            clip: rect(44px, 450px, 56px, 0);
            animation: glitch-anim2 5s infinite linear alternate-reverse;
        }

        @keyframes glitch-anim {
            0% {
                clip: rect(38px, 9999px, 81px, 0);
            }

            5% {
                clip: rect(66px, 9999px, 5px, 0);
            }

            10% {
                clip: rect(98px, 9999px, 83px, 0);
            }

            15% {
                clip: rect(7px, 9999px, 92px, 0);
            }

            20% {
                clip: rect(69px, 9999px, 14px, 0);
            }

            25% {
                clip: rect(79px, 9999px, 49px, 0);
            }

            30% {
                clip: rect(54px, 9999px, 85px, 0);
            }

            35% {
                clip: rect(15px, 9999px, 2px, 0);
            }

            40% {
                clip: rect(8px, 9999px, 82px, 0);
            }

            45% {
                clip: rect(99px, 9999px, 3px, 0);
            }

            50% {
                clip: rect(32px, 9999px, 66px, 0);
            }

            55% {
                clip: rect(57px, 9999px, 53px, 0);
            }

            60% {
                clip: rect(10px, 9999px, 85px, 0);
            }

            65% {
                clip: rect(93px, 9999px, 69px, 0);
            }

            70% {
                clip: rect(97px, 9999px, 41px, 0);
            }

            75% {
                clip: rect(21px, 9999px, 5px, 0);
            }

            80% {
                clip: rect(97px, 9999px, 14px, 0);
            }

            85% {
                clip: rect(47px, 9999px, 96px, 0);
            }

            90% {
                clip: rect(70px, 9999px, 65px, 0);
            }

            95% {
                clip: rect(14px, 9999px, 33px, 0);
            }

            100% {
                clip: rect(62px, 9999px, 63px, 0);
            }
        }

        @keyframes glitch-anim2 {
            0% {
                clip: rect(67px, 9999px, 7px, 0);
            }

            5% {
                clip: rect(81px, 9999px, 90px, 0);
            }

            10% {
                clip: rect(44px, 9999px, 18px, 0);
            }

            15% {
                clip: rect(59px, 9999px, 16px, 0);
            }

            20% {
                clip: rect(25px, 9999px, 98px, 0);
            }

            25% {
                clip: rect(2px, 9999px, 46px, 0);
            }

            30% {
                clip: rect(19px, 9999px, 89px, 0);
            }

            35% {
                clip: rect(51px, 9999px, 59px, 0);
            }

            40% {
                clip: rect(43px, 9999px, 96px, 0);
            }

            45% {
                clip: rect(91px, 9999px, 59px, 0);
            }

            50% {
                clip: rect(31px, 9999px, 34px, 0);
            }

            55% {
                clip: rect(9px, 9999px, 14px, 0);
            }

            60% {
                clip: rect(57px, 9999px, 11px, 0);
            }

            65% {
                clip: rect(31px, 9999px, 3px, 0);
            }

            70% {
                clip: rect(2px, 9999px, 90px, 0);
            }

            75% {
                clip: rect(25px, 9999px, 51px, 0);
            }

            80% {
                clip: rect(17px, 9999px, 58px, 0);
            }

            85% {
                clip: rect(82px, 9999px, 73px, 0);
            }

            90% {
                clip: rect(34px, 9999px, 44px, 0);
            }

            95% {
                clip: rect(8px, 9999px, 97px, 0);
            }

            100% {
                clip: rect(59px, 9999px, 52px, 0);
            }
        }

        .industrial-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            color: white;
            padding: 12px 15px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
            border-radius: 2px;
        }

        .industrial-input:focus {
            border-color: var(--red);
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.1);
            background: rgba(0, 0, 0, 0.6);
        }

        .btn-action {
            width: 100%;
            background: var(--red);
            color: white;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            padding: 15px;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }

        .btn-action:hover {
            background: #cc0000;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.4);
        }

        .status-light {
            width: 8px;
            height: 8px;
            background-color: #00FF00;
            border-radius: 50%;
            box-shadow: 0 0 5px #00FF00;
            display: inline-block;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.5;
            }
        }

        /* Popup Styling */
        #successPopup {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="mb-8 text-center">
            <h1 class="glitch text-3xl font-black text-white" data-text="HRMS_PRO">HRMS_PRO</h1>
            <div class="text-[10px] text-red-500 tracking-[5px] mt-1 font-mono uppercase">System Operator Provisioning
            </div>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-900/30 border border-red-500 text-red-400 px-4 py-3 rounded mb-6 text-sm flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST"
            class="space-y-6">

            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide font-mono">Operator
                    Identity</label>
                <input type="text" id="name" name="name" class="industrial-input" placeholder="FULL NAME" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide font-mono">Operator ID
                    / Phone Number</label>
                <input type="tel" id="phone" name="phone" class="industrial-input" placeholder="MOBILE NUMBER" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide font-mono">Security
                        Code</label>
                    <input type="password" id="password" name="password" class="industrial-input" placeholder="••••••••"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide font-mono">Confirm
                        Code</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="industrial-input"
                        placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" id="btnSubmit" class="btn-action mt-4">
                INITIALIZE_OPERATOR
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-800 flex justify-between items-center text-xs">
            <a href="index.php" class="text-gray-400 hover:text-red-500 transition font-mono"><i
                    class="fas fa-arrow-left mr-2"></i>RETURN_TO_LOGIN</a>
            <div class="text-[9px] text-gray-600 font-mono">
                <span class="status-light"></span> SYSTEM_READY
            </div>
        </div>
    </div>

    <!-- Success Popup -->
    <div id="successPopup" class="fixed inset-0 items-center justify-center p-4">
        <div
            class="bg-[#111] border-2 border-green-500 p-8 rounded-sm text-center max-w-sm w-full mx-auto shadow-[0_0_30px_rgba(0,255,0,0.2)] transform transition-all duration-300">
            <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-3xl text-green-500"></i>
            </div>
            <h2 class="font-['Orbitron'] text-xl text-green-500 tracking-wider mb-2 uppercase font-black">Success</h2>
            <p class="text-gray-400 font-mono text-xs tracking-widest mb-8">OPERATOR GRANTED CLEARANCE</p>
            <div class="text-[10px] text-gray-600 font-mono mt-4 animate-pulse">REDIRECTING_TO_LOGIN...</div>
        </div>
    </div>

    <!-- Inject Success Script -->
    <?php if ($success): ?>
        <script>
            document.getElementById('successPopup').style.display = 'flex';
            setTimeout(function () {
                window.location.href = 'index.php';
            }, 3000);
        </script>
    <?php endif; ?>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function () {
            const btn = document.getElementById('btnSubmit');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> PROCESSING...';
            btn.disabled = true;
            btn.style.opacity = 0.8;
            btn.style.cursor = 'not-allowed';
        });
    </script>
</body>

</html>