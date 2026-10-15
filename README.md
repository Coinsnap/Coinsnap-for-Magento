# Coinsnap for Magento

![Coinsnap for Magento](https://resources.coinsnap.org/products/magento/images/cover.png)

**Bitcoin payment module for Magento 2 — accept Bitcoin and Lightning with Coinsnap.**

Coinsnap for Magento is an open-source payment module that adds Bitcoin payments to Magento 2 stores and integrates them into the existing checkout and order workflow.

Customers can pay with **Bitcoin via Lightning or on-chain**. When using Coinsnap, merchants do not need to operate their own Bitcoin or Lightning payment infrastructure.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

## Key features

- Accept **Bitcoin and Lightning payments** in Magento 2
- Support for **Lightning and Bitcoin on-chain**
- Native integration into the Magento checkout
- Payment status handling through the Coinsnap integration
- No own Lightning node or Bitcoin payment server required
- **Non-custodial / self-custody** approach
- Settlement to a compatible Bitcoin or Lightning wallet
- Open-source Magento 2 module

## Requirements

- **Magento 2**
- **PHP 7.4 or newer**
- A **Coinsnap merchant account**

For Bitcoin settlement, you can use a compatible Bitcoin or Lightning wallet. One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/), which combines a self-custodial Bitcoin wallet with merchant and point-of-sale functionality.

## How it works

1. Create a [Coinsnap merchant account](https://app.coinsnap.io/register).
2. Download the Coinsnap for Magento module from this repository.
3. Upload the module to your Magento installation.
4. Enable the module and run the Magento setup commands.
5. Enter your Coinsnap Store ID and API key in the Magento payment settings.
6. Enable the Bitcoin payment method.
7. Perform a test transaction before going live.

## Installation

### 1. Download the module

Download the current version from this GitHub repository.

### 2. Upload the module

Unzip the archive and upload the complete **Coinsnap** directory to:

`app/code/`

### 3. Enable the module

Run the following commands from the root directory of your Magento installation:

```bash
php bin/magento module:enable Coinsnap_Payment
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

Depending on your Magento deployment, you may also need to clear or refresh the Magento cache after installation.

### 4. Configure Coinsnap

In the Magento administration:

1. Go to **Stores → Configuration**.
2. Open **Sales → Payment Methods**.
3. Find the **Coinsnap** payment method.
4. Enter your **Coinsnap Store ID**.
5. Enter your **Coinsnap API key**.
6. Save the configuration.
7. Enable the payment method and perform a test payment.

Your Store ID and API key are available in your Coinsnap merchant account.

## Resources

- [Coinsnap Magento demo store](https://magento.coinsnap.org/)
- [Coinsnap for Magento article](https://coinsnap.io/coinsnap-for-magento-payment-plugin/)
- [Adobe Commerce Marketplace listing](https://commercemarketplace.adobe.com/coinsnap-payment.html)
- [Coinsnap Wallet](https://coinsnap.io/wallet/)
- [Coinsnap API documentation](https://docs.coinsnap.io/)
- [Coinsnap merchant account](https://app.coinsnap.io/register)
- [Coinsnap website](https://coinsnap.io/)
- [Telegram community and support](https://t.me/coinsnap)

> **Brand note:** Coinsnap is Bitcoin payment software for merchants and is not the provider of the similarly named coin identifier app "CoinSnap".

## Frequently Asked Questions

### What are the requirements for accepting Bitcoin in my Magento store?

You need a Magento 2 store, PHP 7.4 or newer, and a Coinsnap merchant account. You do not need to run your own Lightning node or Bitcoin payment server.

### Can customers pay with both Lightning and Bitcoin on-chain?

Yes. Coinsnap supports Bitcoin payments via both the Lightning Network and Bitcoin on-chain.

### Which Bitcoin or Lightning wallets can I use?

You can use a compatible Bitcoin or Lightning wallet for settlement. One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/).

Other compatible wallets may include solutions such as Wallet of Satoshi, Blink and Zeus, depending on the settlement setup you choose.

### Does Coinsnap hold my Bitcoin?

Coinsnap follows a non-custodial approach and does not hold merchant Bitcoin. Payments are settled to the merchant's configured wallet or payment destination.

### Do I need technical knowledge to install the module?

The Magento installation requires access to the server file system and command line. If you already manage Magento extensions through SSH and `bin/magento`, the installation process is straightforward.

### Where do I find my Store ID and API key?

They are available in your Coinsnap merchant account and are entered in the Magento payment-method configuration.

### Is the module available worldwide?

Coinsnap is designed for merchants worldwide. Bitcoin and Lightning payments can be accepted from customers globally, subject to any legal or service restrictions that may apply in the merchant's jurisdiction.

### Where can I get support?

Use the support area in your Coinsnap merchant account or join the official [Coinsnap Telegram channel](https://t.me/coinsnap).

## Changelog

### 1.0.0 — 2024-04-02

- First public release.
