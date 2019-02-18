<h2>Auth</h2>
<form action="auth.php" method="get">
<div>Amount: <input type="text" name="amount" value="5.00" /></div>
<div>Order ID: <input type="text" name="order" value="123456" /></div>
<div>Currency: <input type="text" name="currency" value="RON" /></div>
<div>Desc: <input type="text" name="desc" value="Order 123456" /></div>
<input type="submit" value="Show Authorize Form" />
</form>

<h2>Capture</h2>
<form action="capture.php" method="get">
<div>Amount: <input type="text" name="amount" value="5.00" /></div>
<div>Order ID: <input type="text" name="order" value="123456" /></div>
<div>Currency: <input type="text" name="currency" value="RON" /></div>
<div>RRN: <input type="text" name="rrn" value="<?php echo @$_GET['rrn'] ?>" /></div>
<div>Int Ref: <input type="text" name="intref" value="<?php echo @$_GET['intref'] ?>" /></div>
<input type="submit" value="Capture" />
</form>

<h2>Void</h2>
<form action="void.php" method="get">
<div>Amount: <input type="text" name="amount" value="5.00" /></div>
<div>Order ID: <input type="text" name="order" value="123456" /></div>
<div>Currency: <input type="text" name="currency" value="RON" /></div>
<div>RRN: <input type="text" name="rrn" value="<?php echo @$_GET['rrn'] ?>" /></div>
<div>Int Ref: <input type="text" name="intref" value="<?php echo @$_GET['intref'] ?>" /></div>
<input type="submit" value="Void" />
</form>