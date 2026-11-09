# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **February 12, 2026** - IP value removed from checkout session creation
- **January 26, 2026** - Prevent duplicate payments, reduce product stock when a payment retry is successful
  - Implemented duplicate payment validation system
  - Improved stock management during successful payment retries
  - Optimized order status synchronization after a successful retry
- **October 23, 2025** - Update for Davipay checkout version 2
  - Updated checkout configuration payload to include `checkout_version: "2"`
  - Implemented integration with the new `payment/session/create` endpoint to create payment sessions
  - Improved checkout configuration supporting dynamic `sessionId`
  - Updated JavaScript checkout configuration to use the new session system
  - Fixed `autoClick` setting to `false` for better control over the payment flow

### Added

- **New fields in the checkout payload:**
  - `checkout_version`: "2" - Specifies the checkout version to use
  - `sessionId`: Dynamic field obtained from the `payment/session/create` response
  - `autoClick`: false - Improved control over the checkout opening flow

### Technical Details

- Modified `classes/class-wc-gateway-epayco.php`:
  - Updated the `generate_epayco_form()` function to include a call to `getEpaycoSessionId()`
  - Implemented logic to obtain `sessionId` from the API response
  - Improved JavaScript checkout object configuration with session parameters
  - Updated payload structure for v2 checkout compatibility

### Security

- Implemented secure handling of Bearer tokens for Davipay API authentication
- Improved API response validation prior to processing session data

## [Previous Versions]

### [1.1.0] - Previous versions

#### Core Functionality

- Core functionality of the Davipay plugin for WooCommerce
- Davipay API integration for payment processing
- Automated order and payment status management
- Signature validation system for transaction security

#### Key Features

- Support for multiple payment methods (credit cards, debit cards, PSE, etc.)
- Automatic stock management and restoration in case of failures
- Cron job system for status synchronization
- Customizable confirmation and response URL configurations
- Support for test and production modes
- Public and private key validation
- Multi-currency support (COP, USD)

#### Technical Implementation

- Main class `WC_Gateway_Epayco` extending `WC_Payment_Gateway`
- Integrated logging system for debugging
- Robust exception and error handling
- SHA256 signature validation for security
- REST API endpoints for payment confirmation and validation
- Metadata system for transaction tracking

#### Security Features

- Digital signature validation
- Secure credential management
- Input data sanitization
- Protection against direct file access
- IP and transaction data validation