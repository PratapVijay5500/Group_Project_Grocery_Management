<?php include 'db_connect.php';

if(isset($_GET['id'])){
	$qry = $conn->query("SELECT * FROM sales_list where id=".$_GET['id'])->fetch_array();
	foreach($qry as $k => $val){
		$$k = $val;
	}
	$inv = $conn->query("SELECT * FROM inventory where form_id=".$_GET['id']);

}

?>
<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<h4><b>View Requests</b></h4>
			</div>
			<div class="card-body">
			<form action="" id="po-form"> 
			<div class="col-md-12"> 
							
				<div class="row">
					<table class="table table-bordered table-hover" id="list">
					<colgroup>
									<col width="10%">
									<col width="25%">
									<col width="5%">
									<col width="20%">
									<col width="10%">
									<col width="10%">
									<col width="20%">


					</colgroup>
                       <thead>
					   <tr>
										<th class="text-center">#</th>
										<th class="text-center">Product</th>
										<th class="text-center">Qty</th>
										<th class="text-center">Date</th>
										<th class="text-center">Reference #</th>
										<th class="text-center">Issued By</th> 
										<th class="text-center">Comment</th> 
									</tr>
                        </thead>
                        <tbody>
							<?php 
							$i = 1;
							$cat = $conn->query("SELECT * FROM category_list order by name asc");
							while($row=$cat->fetch_assoc()):
								$cat_arr[$row['id']] = $row['name'];
							endwhile;
							$product = $conn->query("SELECT * FROM product_list  order by name asc");
							while($row=$product->fetch_assoc()):
								$prod[$row['id']] = $row;
							endwhile;
							$customer = $conn->query("SELECT * FROM customer_list order by name asc");
							
							while($row=$customer->fetch_assoc()):
								$cus_arr[$row['id']] = $row['name'];
							endwhile;
							$cus_arr[0] = "GUEST";
							if(isset($id)):
								while($row = $inv->fetch_assoc()): 
									foreach(json_decode($row['other_details']) as $k=>$v){
										$row[$k] = $v;
									}
							 ?>
							 <tr class="item-row">
								<td class="text-center"><?=$i++?></td>
								<!-- <td class="text-center"><?=$cus_arr[$row['customer_id']]?></td> -->
								<td>
								 <input type="hidden" name="inv_id[]" value="<?php echo $row['id'] ?>">
								 <input type="hidden" name="product_id[]" value="<?php echo $row['product_id'] ?>">
								 <p class="pname">Name: <b><?php echo $prod[$row['product_id']]['name'] ?></b></p>
								 <p class="pdesc"><small><i>Description: <b><?php echo $prod[$row['product_id']]['description'] ?></b></i></small></p>
								</td>
								<td class="text-center"><?=$row['qty']?></td>
								<td class="text-center"><?=$row['date_updated']?></td>
								<?php
								$sales = $conn->query("SELECT * FROM sales_list Where id=".$_GET['id']);
								while($row1 = $sales->fetch_assoc()):?>
								<td class="text-center"><?=$row1['ref_no']?></td> 
								<?php endwhile; ?>
								<td class="text-center"><?=$row['issued']?></td>

								
									<?php
								$sales = $conn->query("SELECT * FROM sales_list Where id=".$_GET['id']);
								while($row1 = $sales->fetch_assoc()):?>
								
								<td class="text-center"><?=$row1['comment']?></td>
								<?php endwhile; ?>
								<?php endwhile; ?>
									<?php endif; ?>
                        
								</tr>
								
						</tbody>
					</table>
			    </div>
			</div>
			</form>
		    </div>
		</div>
	</div>
</div>
<style type="text/css">
	#tr_clone{
		display: none;
	}
	td{
		vertical-align: middle;
	}
	td p {
		margin: unset;
	}
	td input[type='number']{
		height: calc(100%);
		width: calc(100%);

	}
	input[type=number]::-webkit-inner-spin-button, 
	input[type=number]::-webkit-outer-spin-button { 
	  -webkit-appearance: none; 
	  margin: 0; 
	}
</style>
<script>
	$('table').dataTable()
	$('.select2').select2({
	 	placeholder:"Please select here",
	 	width:"100%"
	})
	$(document).ready(function(){
		if('<?php echo isset($id) ?>' == 1){
			$('[name="supplier_id"]').val('<?php echo isset($supplier_id) ? $supplier_id :'' ?>').select2({
				placeholder:"Please select here",
	 			width:"100%"
			})
			calculate_total()
		}
	})
	function rem_list(_this){
		_this.closest('tr').remove()
	}
	
$('#accepted-issue').submit(function(e){
		e.preventDefault()
		start_load()
		$.ajax({
			url:'ajax.php?action=update_status',
		    method: 'POST',
		    data: $(this).serialize(),
			success:function(resp){
				alert_toast("Data successfully submitted",'success')
				location.href = "index.php?page=requests_status"
				
			}
		})
	})
			$('#po-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			$('.err-msg').remove();
			$('[name="po_no"]').removeClass('border-danger')
			if($('.po-item').length <= 0){
				alert_toast(" Please add atleast 1 item on the list.",'warning')
				return false;
			}
			start_loader();
			$.ajax({
				url:'ajax.php?action=approve_sale',
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.href = "./?page=requests_status";
					}else if((resp.status == 'failed' || resp.status == 'po_failed') && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body").animate({ scrollTop: 0 }, "fast");
                            end_loader()
							if(resp.status == 'po_failed'){
								$('[name="po_no"]').addClass('border-danger').focus()
							}
                    }else{
						alert_toast("An error occured",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})
</script>
