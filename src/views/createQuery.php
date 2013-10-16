<?php
/* @var $this Mouf\Database\QueryWriter\Controllers\SelectController */
?>
<h1>Create a new SQL query</h1>

<?php if ($this->parseError) { ?>
	<div class="alert">Unable to parse SQL query</div>
<?php } ?>

<form action="doCreateQuery" method="post" class="form-horizontal">
	<div class="control-group">
		<label class="control-label">Instance name*: </label>
		<div class="controls">
			<input type="text" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" required />
			<span class="help-block">The name of the <code>Select</code> instance that will be created.</span>
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label">SQL query*: </label>
		<div class="controls">
			<textarea rows=10 name="sql" class="span10" required><?php echo plainstring_to_htmlprotected($this->sql) ?></textarea>
			<span class="help-block">You can use <strong>parameters</strong> using prepared statement notation. For instance: 
			<code>select * from users where country_id = :country_id</code></span>
		</div>
	</div>
	
	<div class="control-group">
		<div class="controls">
			<button name="action" value="parse" type="submit" class="btn btn-danger">Create SQL query object</button>
		</div>
	</div>
		
</form>

<div class="alert">If you are performing JOINs, avoid doing a `SELECT * FROM ...`. You may have
several columns with the same name in different tables and this might confuse QueryWritter, especially
if you are using the `CountNbResult` class.</div>


<script type="text/javascript">
$(function () { $("input,select,textarea").not("[type=submit]").jqBootstrapValidation(); } );
</script>