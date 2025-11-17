<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email - SkinLab Beauty</title>
    <style>
        /* Tambahkan nilai untuk background */
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #ec4899, #db2777); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; }
        .header p { margin: 5px 0 0; font-size: 1.1em; }
        .content { padding: 30px; line-height: 1.6; }
        .code { font-size: 32px; font-weight: bold; color: #db2777; text-align: center; margin: 20px 0; letter-spacing: 2px; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✨ SkinLab Beauty</h1>
            <p>Verify Your Email</p>
        </div>
        
        <div class="content">
            <p>Hello!</p>
            <p>Your verification code is:</p>
            
            <div class="code">{{ $code }}</div>
            
            <p>This code will expire in <strong>{{ $expires }} minutes</strong>.</p>
            <p>If you didn't request this, please ignore this email.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} SkinLab Beauty. All rights reserved.</p>
        </div>
    </div>
</body>
</html>