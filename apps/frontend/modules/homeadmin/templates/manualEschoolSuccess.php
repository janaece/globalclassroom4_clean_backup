<script type="text/javascript" src="/js/lib.js"></script>
<div class="gc_small_form" style="width:50%"> 
    <table class="content" cellpadding="10" >
        <tr>
            <td colspan="2">
                <div>
                    <h1>Process eSchool Activation Fees Paid by Check</h1>
                    Please enter the total amount written on the check, the check number, and the date until which this manual payment
                    pays the eSchool activation fees.
                </div>
                <br />
            </td>
        </tr>
        <tr>
            <td style="float:right">
    <?php include_partial('eschoolManualForm', array(	'eschoolManualForm' => $eschool_form,
                                                        'eschool_name' => $eschool->getShortName(),
                                                        'return_url' => $return_url)); ?>
            </td>
            <td> &nbsp</td>
        </tr>
    </table>
</div>