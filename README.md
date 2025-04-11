
![WP Amazon Post Products](https://i.postimg.cc/3r2MBMV3/wp-amazon-product-grid.jpg)

# WP Amazon Post Product

![GitHub Release](https://img.shields.io/github/v/release/fabioferrero90/wp-amazon-post-products)
![GitHub last commit](https://img.shields.io/github/last-commit/fabioferrero90/wp-amazon-post-products)
[![GitHub issues](https://img.shields.io/github/issues-raw/fabioferrero90/wp-amazon-post-products)](https://img.shields.io/github/issues-raw/fabioferrero90/wp-amazon-post-products)
[![GitHub pull requests](https://img.shields.io/github/issues-pr/fabioferrero90/wp-amazon-post-products)](https://img.shields.io/github/issues-pr/fabioferrero90/wp-amazon-post-products)
[![GitHub](https://img.shields.io/github/license/fabioferrero90/wp-amazon-post-products)](https://img.shields.io/github/license/fabioferrero90/wp-amazon-post-products)

A WordPress plugin that automatically detects Amazon product links in your content and displays them in an elegant, responsive grid or slider.

## Features

- **Automatic Product Detection**: Automatically finds Amazon product links (amzn.eu short links) in your post content
- **Responsive Grid Display**: Shows products in a beautiful, mobile-friendly Swiper slider
- **Amazon API Integration**: Uses the Amazon Product Advertising API (PA-API 5.0) to fetch accurate product data
- **Caching System**: Implements an efficient caching system to improve performance and reduce API calls
- **Customizable Appearance**: Easily customize the grid title, subtitle, and styling
- **Elementor Compatible**: Works seamlessly with Elementor page builder

## How It Works

1. The plugin scans your post content for Amazon product links (amzn.eu format)
2. It expands these short links to extract the Amazon Standard Identification Number (ASIN)
3. Using Amazon's Product Advertising API, it fetches product details including title and image
4. Products are displayed in a responsive Swiper slider with Amazon branding
5. All product data is cached to improve performance on subsequent page loads

## Usage

Simply add the shortcode `[prodotti_amazon]` to any post or page where you want to display Amazon products. Make sure your post content includes Amazon product links in the amzn.eu format.

### Using with Elementor

1. Edit your template with Elementor
2. Add a "Shortcode" widget to your layout
3. Insert `[prodotti_amazon]` in the shortcode field
4. Save and publish your template

## Configuration

In the WordPress admin area, navigate to Settings > Amazon Products Grid to configure:

- Amazon API credentials (Access Key, Secret Key, Associate Tag)
- Region settings
- Grid title and subtitle
- Display options and custom CSS

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Active Amazon Product Advertising API account
