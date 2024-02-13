# Coinsnap for Magento payment module

== Description ==

Magento is an open source e-commerce platform that enables companies to create and manage online shops. A Magento plugin, also known as an extension, is a piece of software that extends or improves the functionality of the Magento platform.

These plugins are developed to add specific features, improve performance or integrate with third-party services to provide a more customised and feature-rich e-commerce experience. With the Coinsnap payment module for Magento, shop operators can accept Bitcoin and Lightning payments from their customers.

The included text contains a comprehensive guide to help you seamlessly integrate Magento with the Coinsnap app.

== Installation ==

## Install Magento plugin via Github ##

The Magento plugin can be downloaded from the GitHub page here. The installation is very simple, but you need to know how to use FTP and SSH clients to complete the whole process. Download the ZIP file by clicking on the green button “Clone or download” and “Download ZIP”.

Unzip the file and upload the entire “Coinsnap” directory to the app/code folder. Execute the following commands in the shell (with an SSH client) in the root directory of the website:

1. php bin/magento module:enable Coinsnap_Payment

2. php bin/magento setup:upgrade

3. php bin/magento setup:di:compile

You can then log in to the Magento dashboard.

![](https://github.com/Coinsnap/Coinsnap-for-Magento/blob/main/assets/configuration.png)

#### (1) Navigate to stores ####
Find the “Stores” section on the left-hand side of the Magento login page and click on it.

#### (2) Access to the configuration ####
In the “Stores” section, search for the “Configuration” subsection. Click on it to continue.

![](https://github.com/Coinsnap/Coinsnap-for-Magento/blob/main/assets/sales.png)

Search for the “Sales” category in the “Configuration” section and select it.
