#!/bin/bash
DEBIAN_FRONTEND=noninteractive
echo "tzdata tzdata/Areas select Etc" | debconf-set-selections
echo "tzdata tzdata/Zones/Etc select UTC" | debconf-set-selections

apt-get update
apt-get install -y tzdata