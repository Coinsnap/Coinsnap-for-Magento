# Coinsnap for Magento 1.0

Accept Bitcoin and Ligtning payments in Magento 2.4 with Coinsnap

Magento version: 2.4

# Installation instructions

- Unzip zip file 
- Update files as per directory in (app/code) folder 
- Run following command in shell (ssh command) from website root folder 
	php  bin/magento module:enable Coinsnap_Payment
	php  bin/magento setup:upgrade
	php  bin/magento setup:di:compile
- Login  Magento admin panel select Stores >> Configuration >>  Sales >>Payment Methods >> Coinsnap
- Enter your Coinsnap Store ID , API Key

