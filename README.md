# Coinsnap for Magento payment plugin #
![Image of Coinsnap for Magento](https://coinsnap.io/wp-content/uploads/2023/11/coinsnap-for-magento.png)
## Accept Bitcoin and Lightning Payments with Magento ##

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

![](https://coinsnap.io/wp-content/uploads/2023/12/Screenshot-2023-12-21-at-19.22.00.png)

#### (1) Navigate to stores ####
Find the “Stores” section on the left-hand side of the Magento login page and click on it.

#### (2) Access to the configuration ####
In the “Stores” section, search for the “Configuration” subsection. Click on it to continue.

After search for the “Sales” category in the “Configuration” section and select it. Now enter your login details in your Coinsnap app and go to the settings.

In the settings, you will find the “Store settings” section, where you can retrieve your store ID and API key. Copy these login details for future use.

![](https://coinsnap.io/wp-content/uploads/2023/11/Screenshot-2023-11-30-at-10.22.46.png)

#### (1) Configuration of the payment methods ####
Navigate to the “Sales” section and search for the “Payment methods” option. Click on them.

#### (2) Integration of the Coinsnap app data ####
Enter the store ID and API key that you copied from the Coinsnap app into the corresponding fields.

#### (3) Save changes ####
Click on the “Save” button to finalise the integration of your Magento plugin with the Coinsnap app. Now you’re ready to accept Bitcoin+Lightning payments.

![](https://coinsnap.io/wp-content/uploads/2023/12/Screenshot-2023-12-21-at-19.26.32.png)

Feel free to change the other fields visible in Magento (similar to the image), but make sure that the “Title” field retains the name “Bitcoin+Lightning”.
