<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microsoft Outlook OAuth Integration</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #0078d4 0%, #106ebe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0078d4;
        }

        .button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0078d4 0%, #106ebe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,120,212,0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,193,7,0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0078d4;
        }

        .status-connected {
            border-left-color: #28a745;
            background: #d4edda;
        }

        .status-expired {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        .status-expiring {
            border-left-color: #ffc107;
            background: #fff3cd;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .status-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .status-label {
            font-weight: 600;
            color: #495057;
        }

        .status-value {
            color: #6c757d;
        }

        .status-value.connected {
            color: #28a745;
            font-weight: 600;
        }

        .status-value.expired {
            color: #dc3545;
            font-weight: 600;
        }

        .status-value.expiring {
            color: #ffc107;
            font-weight: 600;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0078d4;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .test-email-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .test-email-section h3 {
            margin-bottom: 15px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Outlook OAuth</h1>
            <p>Connect and manage your Microsoft Outlook email integration</p>
        </div>

        <div class="content">
            <div class="alert alert-success" id="successAlert"></div>
            <div class="alert alert-error" id="errorAlert"></div>

            <div class="form-group">
                <label for="email">Email Address (for checking existing connections):</label>
                <input type="email" id="email" placeholder="Enter email to check existing connection status">
            </div>

            <div class="button-group">
                <button class="btn btn-primary" id="connectBtn" onclick="connectOutlook()">
                    🔗 Connect Outlook
                </button>
                <button class="btn btn-success" id="refreshBtn" onclick="refreshToken()" disabled>
                    🔄 Refresh Token
                </button>
                <button class="btn btn-warning" id="testEmailBtn" onclick="sendTestEmail()" disabled>
                    📧 Send Test Email
                </button>
                <button class="btn btn-danger" id="clearBtn" onclick="disconnectCurrentAccount()" disabled>
                    🗑️ Disconnect Outlook
                </button>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Processing...</p>
            </div>

            <div class="status-card" id="statusCard" style="display: none;">
                <h3>📊 Connection Status</h3>
                <div id="statusContent"></div>
            </div>

            <div class="test-email-section" id="testEmailSection" style="display: none;">
                <h3>📧 Test Email</h3>
                <div class="form-group">
                    <label for="toEmail">Send to (optional):</label>
                    <input type="email" id="toEmail" placeholder="Leave empty to send to yourself">
                </div>
            </div>
        </div>
    </div>

    <script>
        // CSRF token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Show/hide alerts
        function showAlert(message, type = 'success') {
            const alertId = type === 'success' ? 'successAlert' : 'errorAlert';
            const alert = document.getElementById(alertId);
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Show/hide loading
        function showLoading(show = true) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        // Check token status
        async function checkTokenStatus() {
            const email = document.getElementById('email').value;
            if (!email) return;

            try {
                const response = await fetch('/token-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();
                updateUI(data);
            } catch (error) {
                console.error('Error checking token status:', error);
            }
        }

        // Update UI based on status
        function updateUI(status) {
            const statusCard = document.getElementById('statusCard');
            const statusContent = document.getElementById('statusContent');
            const connectBtn = document.getElementById('connectBtn');
            const refreshBtn = document.getElementById('refreshBtn');
            const testEmailBtn = document.getElementById('testEmailBtn');
            const clearBtn = document.getElementById('clearBtn');
            const testEmailSection = document.getElementById('testEmailSection');

            if (status.connected) {
                statusCard.style.display = 'block';
                statusCard.className = 'status-card status-connected';
                
                let statusHtml = `
                    <div class="status-item">
                        <span class="status-label">Status:</span>
                        <span class="status-value connected">✅ Connected</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Email:</span>
                        <span class="status-value">${status.email}</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Expires:</span>
                        <span class="status-value">${status.expires_at || 'Unknown'}</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Time Remaining:</span>
                        <span class="status-value ${status.is_expired ? 'expired' : status.is_expiring_soon ? 'expiring' : ''}">${status.expires_in}</span>
                    </div>
                `;
                
                statusContent.innerHTML = statusHtml;
                
                // Update button states
                connectBtn.disabled = true;
                refreshBtn.disabled = false;
                testEmailBtn.disabled = false;
                clearBtn.disabled = false;
                testEmailSection.style.display = 'block';
                
                // Update status card color based on token state
                if (status.is_expired) {
                    statusCard.className = 'status-card status-expired';
                } else if (status.is_expiring_soon) {
                    statusCard.className = 'status-card status-expiring';
                }
            } else {
                statusCard.style.display = 'block';
                statusCard.className = 'status-card';
                statusContent.innerHTML = `
                    <div class="status-item">
                        <span class="status-label">Status:</span>
                        <span class="status-value">❌ Not Connected</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Message:</span>
                        <span class="status-value">${status.message || 'Please connect to Outlook first'}</span>
                    </div>
                `;
                
                // Update button states
                connectBtn.disabled = false;
                refreshBtn.disabled = true;
                testEmailBtn.disabled = true;
                clearBtn.disabled = true;
                testEmailSection.style.display = 'none';
            }
        }

        // Connect to Outlook
        function connectOutlook() {
            window.location.href = '/connect-ms';
        }

        // Refresh token
        async function refreshToken() {
            const email = document.getElementById('email').value;
            if (!email) {
                showAlert('Please enter your email address', 'error');
                return;
            }

            showLoading(true);
            try {
                const response = await fetch('/refresh-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert(data.message);
                    checkTokenStatus();
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('Error refreshing token: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        // Send test email
        async function sendTestEmail() {
            const email = document.getElementById('email').value;
            const toEmail = document.getElementById('toEmail').value || email;
            
            if (!email) {
                showAlert('Please enter your email address', 'error');
                return;
            }

            showLoading(true);
            try {
                const response = await fetch('/send-test-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email, to_email: toEmail })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert(data.message);
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('Error sending test email: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        // Disconnect current account
        async function disconnectCurrentAccount() {
            const email = document.getElementById('email').value;
            if (!email) {
                showAlert('Please enter your email address first', 'error');
                return;
            }

            if (!confirm(`Are you sure you want to disconnect from ${email}? This will remove all stored credentials and allow you to connect with a fresh account.`)) {
                return;
            }

            showLoading(true);
            try {
                const response = await fetch('/disconnect-current', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert(data.message);
                    
                    // Clear the email field and reset UI
                    document.getElementById('email').value = '';
                    localStorage.removeItem('outlook_email');
                    
                    // Reset UI to initial state
                    resetUI();
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('Error disconnecting account: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }


        // Reset UI to initial state
        function resetUI() {
            const statusCard = document.getElementById('statusCard');
            const connectBtn = document.getElementById('connectBtn');
            const refreshBtn = document.getElementById('refreshBtn');
            const testEmailBtn = document.getElementById('testEmailBtn');
            const clearBtn = document.getElementById('clearBtn');
            const testEmailSection = document.getElementById('testEmailSection');

            // Hide status card
            statusCard.style.display = 'none';
            
            // Reset button states
            connectBtn.disabled = false;
            refreshBtn.disabled = true;
            testEmailBtn.disabled = true;
            clearBtn.disabled = true;
            testEmailSection.style.display = 'none';
            
            // Clear any alerts
            document.getElementById('successAlert').style.display = 'none';
            document.getElementById('errorAlert').style.display = 'none';
        }

        // Check if we're returning from OAuth callback
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check if we just connected successfully
            if (urlParams.get('connected') === '1') {
                const email = urlParams.get('email');
                if (email) {
                    document.getElementById('email').value = email;
                    localStorage.setItem('outlook_email', email);
                    showAlert('Successfully connected to Microsoft Outlook!');
                    checkTokenStatus();
                    
                    // Clean up URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            } else {
                // Restore email from localStorage if no fresh connection
                const savedEmail = localStorage.getItem('outlook_email');
                if (savedEmail) {
                    document.getElementById('email').value = savedEmail;
                    checkTokenStatus();
                }
            }
        });

        // Check status when email changes
        document.getElementById('email').addEventListener('blur', checkTokenStatus);
    </script>
</body>
</html>
