import os
from flask import Flask, Response, request
import urllib.request
import urllib.error

app = Flask(__name__)

# ضع هنا رابط المزود الحالي (الذي يتغير كل 7 أيام)
PROVIDER_M3U_URL = "http://kytv.xyz/playlist/7XN7MAA6FC/7XN7M7ENB2/m3u"

# بيانات ثابتة تضعها في أجهزتك للأبد (يمكنك تغييرها)
FIXED_USER = "moaqeel"
FIXED_PASS = "123456"

@app.route('/get.php')
def proxy():
    username = request.args.get('username')
    password = request.args.get('password')
    
    # التحقق من بياناتك الثابتة
    if username == FIXED_USER and password == FIXED_PASS:
        try:
            req = urllib.request.Request(
                PROVIDER_M3U_URL, 
                headers={'User-Agent': 'VLC/3.0.16'}
            )
            with urllib.request.urlopen(req) as response:
                content = response.read()
            
            return Response(content, mimetype='audio/x-mpegurl')
        except urllib.error.URLError as e:
            return f"Error fetching stream: {e.reason}", 500
    else:
        return "Unauthorized: Invalid Username or Password!", 401

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 10000))
    app.run(host='0.0.0.0', port=port)
