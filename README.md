# Coinsnap for Magento 1.0

Accept Bitcoin and Ligtning payments in Magento 2.4 with Coinsnap

Magento version: 2.4

# Installation instructions

1. Unzip zip file

2. Upload all the directory "Coinsnap" in app/code folder

3. Run following commands in shell (using SSH-client) from website root folder:

php  bin/magento module:enable Coinsnap_Payment
php  bin/magento setup:upgrade
php  bin/magento setup:di:compile

4. Login in Magento admin panel

5. Select Stores >> Configuration >> Sales >>Payment Methods >> Coinsnap

6. Enter your Coinsnap Store ID, API Key and save the data. 
