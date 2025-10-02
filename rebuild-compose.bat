@echo off
TITLE Cloud Terminal Rebuilder (Compose)

set IMAGE_NAME=cloud-terminal

echo --- Tearing down existing services...
:: This stops and removes the containers and networks.
docker-compose down -v

echo --- Forcefully deleting old Docker image...
:: ✨ --- THIS IS THE FIX --- ✨
:: The '-f' flag forces the removal of the old image, preventing conflicts.
docker rmi -f %IMAGE_NAME%

echo --- Building and starting new services...
:: This builds the image from scratch and starts the containers.
docker-compose up --build -d

echo --- Rebuild complete! ---
echo Your app is now running at http://localhost:8000
pause