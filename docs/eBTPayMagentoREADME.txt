/**
 * Installation guide:
 * 
 * DO NOT INSTALL ON LIVE VERSION. 
 * TEST IT BEFORE YOU COMIT TO LIVE.
 *
 * WE ARE NOT RESPONSIBLE FOR ANY PAYMENT OR DATA LOST
 */
 
- Unpack the contents of the zip arhive to the root of your Magento site.
- Refresh your Magento cache
- Logout and then login from Magneto Backend
- Go in System -> Configuration, in Sales -> Payment Methods on "BT Pay - API" tab
- Enter a valid License Key.
  Note: For development if the store address is an IP address or a domain like "xxx.local" you do not need a valid License Key.
        This extension is also valid for all subdomains including sandbox.exemple.com but for this you need a valid License Key for your TLD.
- Enter your RomCard API  
- Go to "BT Pay - Standard"/"BT Pay - Star BT" tab and set "Enabled" = Yes


If the Transaction section do not appear in the Order Info page add this line in:
app/design/adminhtml/default/default/template/sales/order/view/tab/info.phtml

after:
    <?php echo $this->getGiftOptionsHtml() ?>
    <?php if($this->getLayout()->getBlock('btpay_transactions')) echo $this->getLayout()->getBlock('btpay_transactions')->toHtml(); ?>




Full process - Order comments
 
25.10.2012 13:54:53|Closed
Customer Not Notified

25.10.2012 13:54:53|Processing
Customer Notification Not Applicable 
Refunded amount of 354,99 RON online.

25.10.2012 13:54:53|Processing
Customer Not Notified 
Refund response from gateway is valid.
IntRef: **** RRN: ****
ACTION_CODE: 0 RC_CODE: 00 MESSAGE: Approved

25.10.2012 13:54:33|Processing
Customer Notification Not Applicable 
Captured amount of 354,99 RON online.

25.10.2012 13:54:33|Processing
Customer Not Notified 
Capture response from gateway is valid.
IntRef: **** RRN: ****
ACTION_CODE: 0 RC_CODE: 00 MESSAGE: Approved

25.10.2012 13:41:10|Processing
Customer Notification Not Applicable 
Authorized amount of 354,99 RON approved by payment gateway.
IntRef: **** RRN: ****
AUTHORIZATION_CODE: ****
ACTION_CODE: 0 RC_CODE: 00 MESSAGE: Approved

25.10.2012 13:40:50|Payment Review
Customer Notification Not Applicable 
Authorizing amount of 354,99 RON is pending approval on gateway.