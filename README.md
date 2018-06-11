# AgateE-Commerce
# Using the Agate plugin for WordPress (WP) eCommerce

## Prerequisites

* Last Cart Version Tested: Wordpress 4.0 WP e-commerce 3.13.1

You must have a Agate API KEY to use this plugin.  It's free visit [here](http://www.agate.services/registration-form/) .


## Installation of Wordpress eCommerce Plugin

- Download WP eCommerce plugin from the WordPress Plugin Directory: https://wordpress.org/plugins/wp-e-commerce/. 
- Extract the contents of the zip file to the [wordpress main directory]/wp-content/plugins/ directory.
- Log in to your Wordpress and navigate to the Admin dashboard -> Plugins -> Installed Plugins
- Activate WP eCommerce plugin

## Installation of the Agate plugin for WordPress (WP) eCommerce

- Clone the repo:

```bash
$ git clone https://github.com/AgateChain/AgateE-Commerce.git
$ cd AgateE-Commerce
```
Copy the files inside the folder to your_site/wp-content/plugins/wp-e-commerce/wpsc-merchants.

## Configuration

* Log into the WordPress admin panel, click Settings > Store > Payments (assuming you've already installed WP eCommerce plugin).

* Check the Agate payment option to activate it and click Save Changes below.

* Click Settings below the Agate payment option.

* Edit Display Name if desired.

* Enter the API KEY provided by Agate.

* Input a URL to redirect customers after they have paid the invoice (Transaction Results page, Your Account page, etc.)

* Click Update below.

## Usage

- Once the configuration is done, whenever a buyer selects Agate as their payment method a invoice will be generated in agate.services.
- Then they will be redirected to a payment page where there can pay the invoice.

Try How It Works ?
====================

We have created a demo website for you to test the plugin, feel free to visit [here]http://www.agate.services/ecommerce/products-page/) . For checkout visit [cart](http://www.agate.services/ecommerce/products-page/checkout/) .
