#!/bin/bash

# Define variables
KEY_ID="B7B3B788A8D3785C"
KEYRING_PATH="/usr/share/keyrings/mysql-archive-keyring.gpg"
MYSQL_LIST="/etc/apt/sources.list.d/mysql.list"

echo "--- Starting MySQL Key Refresh for Debian 13 ---"

# 1. Download and convert the key to a binary GPG format
echo "[1/3] Fetching new key from Ubuntu keyserver..."
gpg --no-default-keyring --keyring ./temp_keyring.gpg --keyserver keyserver.ubuntu.com --recv-keys $KEY_ID

# 2. Export it to the correct system location
echo "[2/3] Installing key to $KEYRING_PATH..."
gpg --no-default-keyring --keyring ./temp_keyring.gpg --export $KEY_ID | sudo tee $KEYRING_PATH > /dev/null
rm ./temp_keyring.gpg

# 3. Update the source file to use the 'signed-by' option
# This uses sed to insert the signed-by tag if it's missing
echo "[3/3] Updating $MYSQL_LIST to use signed-by..."
sudo sed -i "s|deb http|deb [signed-by=$KEYRING_PATH] http|g" $MYSQL_LIST

# 4. Refresh APT
echo "--- Refreshing APT repositories ---"
sudo apt update
