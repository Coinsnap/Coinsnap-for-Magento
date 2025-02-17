# Coinsnap for Magento payment plugin #
![Coinsnap for Magento](https://resources.coinsnap.org/products/magento/images/cover.png)

## Accept Bitcoin and Lightning Payments with Magento ##

* Contributors: coinsnap
* Tags: Lightning, Lightning Payment, SATS, Satoshi sats, bitcoin, Magento, Adobe Commerce, payment gateway, accept bitcoin, bitcoin plugin, bitcoin payment processor, bitcoin e-commerce, Lightning Network, cryptocurrency, lightning payment processor
* Requires PHP: 7.4
* Requires Magento: 2
* Stable tag: 1.0.0
* License: GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html

The Coinsnap extention for Magento allows you to accept Bitcoin and Ligtning payments in Magento 2 e-shop system.

## Description ##

[Coinsnap](https://coinsnap.io/en/) provides modules and plugins that enable online stores to receive Bitcoin payments from their customers' Bitcoin Lightning wallets to their own Bitcoin Lightning wallets for digital and physical goods.

Magento is an open source e-commerce platform that enables companies to create and manage online shops. A Magento plugin, also known as an extension, is a piece of software that extends or improves the functionality of the Magento platform.

These plugins are developed to add specific features, improve performance or integrate with third-party services to provide a more customised and feature-rich e-commerce experience. With the Coinsnap payment module for Magento, shop operators can accept Bitcoin and Lightning payments from their customers.

* Coinsnap Magento Demo Site: [https://magento.coinsnap.org/](https://magento.coinsnap.org/)
* Blog Article: [https://coinsnap.io/coinsnap-for-magento-payment-plugin/](https://coinsnap.io/coinsnap-for-magento-payment-plugin/)
* Extension page: [https://commercemarketplace.adobe.com/coinsnap-payment.html](https://commercemarketplace.adobe.com/coinsnap-payment.html)
* GitHub: [https://github.com/Coinsnap/Coinsnap-for-Magento](https://github.com/Coinsnap/Coinsnap-for-Magento)

## Bitcoin and Lightning payments in Magento ##

![Magento Shop](https://resources.coinsnap.org/products/magento/images/screenshot-magento.png)

Coinsnap is a Lightning payment provider and offers a payment gateway for processing Bitcoin and Lightning payments. A merchant only needs a Lightning wallet with a lightning address to accept Bitcoin and Lightning payments on his web store.

With the Coinsnap payment plugin for Magento, shop operators can accept Bitcoin and Lightning payments from their customers. You don’t need your own Lightning node or any other technical requirements if you'd like to provide payments via Coinsnap payment gateway. Coinsnap Bitcoin and Lightning payment requires no minimum costs, no fixed contracts, no hidden costs. The Magento payment plugin from Coinsnap can be downloaded and installed free of charge.

Simply register on [Coinsnap](https://app.coinsnap.io/register), enter your own Lightning address, upload and install the Coinsnap payment module in Opencart Shop backend. Add your store ID and your API key which you’ll find in your Coinsnap account, and your customers can pay you with Bitcoin Lightning right away!


## Features ##

* **All you need is your email and a Lightning Wallet with a Lightning address. [Here you can find an overview of suitable Lightning Wallets](https://coinsnap.io/en/lightning-wallet-with-lightning-address/)**

* **Accept Bitcoin and Lightning payments** in your online store **without running your own technical infrastructure.** You do not need your own server, nor do you need to run your own Lightning Node. You also do not need a shop-system, for you can sell right out of your forms using the Coinsnap for Content Form 7-plugin.

* **Quick and easy registration at Coinsnap**: Just enter your email address and your Lightning address – and you are ready to integrate the payment module and start selling for Bitcoin Lightning. You will find the necessary IDs and Keys in your Coinsnap account, too.

* **100% protected privacy**:
    * We do not collect personal data.
    * For the registration you only need an e-mail address, which we will also use to inform you when you have received a payment.
    * No other personal information is required as long as you request a withdrawal to a Lightning address or Bitcoin address.

* **Only 1 % fees!**:
    * No basic fee, no transaction fee, only 1% on the invoice amount with referrer code.
    * Without referrer code the fee is 1.25%.
    * Get a referrer code from our [partners](https://coinsnap.io/en/partner/) and customers and save 0.25% fee.

* **No KYC needed**:
    * Direct, P2P payments (instantly to your Lightning wallet)
    * No intermediaries and paperwork
    * Transaction information is only shared between you and your customer

* **Sophisticated merchant’s admin dashboard in Coinsnap:**:
    * See all your transactions at a glance
    * Follow-up on individual payments
    * See issues with payments
    * Export reports

* **A Bitcoin payment via Lightning offers significant advantages**:
    * Lightning **payments are executed immediately.**
    * Lightning **payments are credited directly to the recipient.**
    * Lightning **payments are inexpensive.**
    * Lightning **payments are guaranteed.** No chargeback risk for the merchant.
    * Lightning **payments can be used worldwide.**
    * Lightning **payments are perfect for micropayments.**

* **Multilingual interface and support**: We speak your language


## Documentation: ##

* [Coinsnap API (1.0) documentation](https://docs.coinsnap.io/)
* [Frequently Asked Questions](https://coinsnap.io/en/faq/) 
* [Terms and Conditions](https://coinsnap.io/en/general-terms-and-conditions/)
* [Privacy Policy](https://coinsnap.io/en/privacy/)

## Installation ##

The included text contains a comprehensive guide to help you seamlessly integrate Magento with the Coinsnap app.


## 1. Install Magento plugin via Github ##

### 1.1. Plugin download ###
The Magento plugin can be downloaded from the GitHub page here. The installation is very simple, but you need to know how to use FTP and SSH clients to complete the whole process. Download the ZIP file by clicking on the green button “Clone or download” and “Download ZIP”.

### 1.2. Extension setup ###
Unzip the file and upload the entire “Coinsnap” directory to the app/code folder. Execute the following commands in the shell (with an SSH client) in the root directory of the website:

* php bin/magento module:enable Coinsnap_Payment
* php bin/magento setup:upgrade
* php bin/magento setup:di:compile

### 1.3. Log-in to Magento Backend ###

You can then log in to the Magento dashboard.

![Navigate to stores - Access to the configuration](https://resources.coinsnap.org/products/magento/images/screenshot-01.png)

##### (1) Navigate to stores #####
Find the “Stores” section on the left-hand side of the Magento login page and click on it.

##### (2) Access to the configuration #####
In the “Stores” section, search for the “Configuration” subsection. Click on it to continue.

### 1.4. Coinsnap plugin configuration ###

After search for the “Sales” category in the “Configuration” section and select it. Now enter your login details in your Coinsnap app and go to the settings.

In the settings, you will find the “Store settings” section, where you can retrieve your store ID and API key. Copy these login details for future use.

![](https://resources.coinsnap.org/products/magento/images/screenshot-02.png)

##### (1) Configuration of the payment methods #####
Navigate to the “Sales” section and search for the “Payment methods” option. Click on them.

##### (2) Integration of the Coinsnap app data #####
Enter the store ID and API key that you copied from the Coinsnap app into the corresponding fields.

##### (3) Save changes #####
Click on the “Save” button to finalise the integration of your Magento plugin with the Coinsnap app. Now you’re ready to accept Bitcoin+Lightning payments.

![](https://resources.coinsnap.org/products/magento/images/screenshot-03.png)

Feel free to change the other fields visible in Magento (similar to the image), but make sure that the “Title” field retains the name “Bitcoin+Lightning”.

If you don’t have a Coinsnap account yet, you can do so via the link shown: [Coinsnap Registration](https://app.coinsnap.io/register)

## 2. Create Coinsnap account ##

### 2.1. Register in Coinsnap App ###

Now go to the Coinsnap website at: [https://app.coinsnap.io/register](https://app.coinsnap.io/register) and open an account by entering your email address and a password of your choice.

![Coinsnap registration](https://resources.coinsnap.org/products/magento/images/screenshot-07.png)

If you are using a Lightning Wallet with Lightning Login, then you can also open a Coinsnap account with it.

### 2.2. Confirm email address ###

You will receive an email to the given email address with a confirmation link, which you have to confirm. If you do not find the email, please check your spam folder.

![E-mail address confirmation](https://resources.coinsnap.org/products/magento/images/screenshot-08.png)

Then please log in to the Coinsnap backend with the appropriate credentials.

### 2.3. Set up website at Coinsnap ###

After you sign up, you will be asked to provide two pieces of information.

In the Website Name field, enter the name of your online store that you want customers to see when they check out.

In the Lightning Address field, enter the Lightning address to which the Bitcoin and Lightning transactions should be forwarded.

A Lightning address is similar to an e-mail address. Lightning payments are forwarded to this Lightning address and paid out. If you don’t have a Lightning address yet, set up a Lightning wallet that will provide you with a Lightning address.

For more information on Lightning addresses and the corresponding Lightning wallet providers, click here:
https://coinsnap.io/lightning-wallet-mit-lightning-adresse/

# Wiki

Read more about the integration configuration on [our Wiki](https://github.com/Coinsnap/Coinsnap-for-Magento).

# Changelog

##### 1.0.0 :: 2024-04-02
* First public release
