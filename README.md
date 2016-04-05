# LWEngine
___
LWEngine (Light Weight Engine) is an engine created in PHP to allow a developer to create websites easier by allowing developers to program their own implementation directly without having to create an underlying structure.

**LWEngine is currently in development, as of yet, there are no examples to demo and the documentation is limited**

# Requirements
Apache, PHP 5.3.0 and higher, (Optional) MySQL
# Installation
These can be installed with these development suites:
* XAMPP (Linux, Mac & Windows)
* LAMP (Linux)
* WampServer (Windows)
* MAMP (Mac, Windows)

#### Terminal installation without LAMP on Ubuntu
```
sudo apt-get install apache2 php5 mysql-server php5-mysql
```
**Optionally**, install `phpmyadmin` if you want a SQL GUI (*do not leave this on a production server*).

**Optionally**, install `openssh-server` if you want to control your webserver via another computer using SSH.

**Optionally**, install `system-config-samba` if you want to share directories to another computer using the SMB protocol.

#### Terminal installation with LAMP on Ubuntu
```
sudo apt-get install lamp-server^
```
# Getting Started
#### Running a live demo
1. Download this repo
2. Copy the files within the ZIP file to your web server's public directory (Most commonly `/var/www` or `/var/www/html` in Ubuntu, or  `C:\XAMP\htdocs` on Windows using XAMPP)
3. Start the Web Server
4. Go to a browser and visit this url http://localhost:80/
5. Setup LWEngine following the instructions and filling-in the necessary information
6. The setup will take you to the demo (http://localhost:80/)

#### Development

**Tip**: *It is recommended that you have a web server available to test any changes you make*

**Note**: LWEngine must be configured to allow LWEngine to work

1. Download this repo
2. Copy the files within the ZIP file to your web server's public directory (Most commonly `/var/www` or `/var/www/html` in Ubuntu, or  `C:\XAMP\htdocs` on Windows using XAMPP)
3. Start the Web Server
4. Go to a browser and visit this url http://localhost:80/
5. Setup LWEngine following the instructions and filling-in the necessary information
6. Start development 

**Please read the examples and/or documentation. The implementation of your website should not interfere with the main functionality of LWEngine, doing so will defeat the purpose of LWEngine itself.**

# License
This software is licensed with the MIT License