echo ""
echo usage:  curl -X POST -d "prompt='a nice penguing;tags=8k octane render, photorealistic --ar 9:20 --v 5'" http://localhost:8062/getimg
php -S 0.0.0.0:8062 apiserver.php
