<?php
$sms_provider = get_user_meta(get_current_user_id(), $this->prefix . 'sms_provider', true);
?>
<table id="" class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_html_e($this->prefix);?>sms_provider">
                    <?php esc_html_e('Phone company', 'better-angels');?>
                </label>
            </th>
            <td>
                <select id="<?php esc_html_e($this->prefix);?>sms_provider" name="<?php esc_html_e($this->prefix);?>sms_provider">
                    <option <?php if ($sms_provider === '') : print 'selected="selected"'; endif;?>></option>
                    <option <?php if ($sms_provider === 'AT&T') : print 'selected="selected"'; endif;?>>AT&amp;T</option>
                    <option <?php if ($sms_provider === 'Alltel') : print 'selected="selected"'; endif;?>>Alltel</option>
                    <option <?php if ($sms_provider === 'Boost Mobile') : print 'selected="selected"'; endif;?>>Boost Mobile</option>
                    <option <?php if ($sms_provider === 'Cricket') : print 'selected="selected"'; endif;?>>Cricket</option>
                    <option <?php if ($sms_provider === 'Metro PCS') : print 'selected="selected"'; endif;?>>Metro PCS</option>
                    <option <?php if ($sms_provider === 'Nextel') : print 'selected="selected"'; endif;?>>Nextel</option>
                    <option <?php if ($sms_provider === 'Ptel') : print 'selected="selected"'; endif;?>>Ptel</option>
                    <option <?php if ($sms_provider === 'Qwest') : print 'selected="selected"'; endif;?>>Qwest</option>
                    <option <?php if ($sms_provider === 'Sprint') : print 'selected="selected"'; endif;?>>Sprint</option>
                    <option <?php if ($sms_provider === 'Suncom') : print 'selected="selected"'; endif;?>>Suncom</option>
                    <option <?php if ($sms_provider === 'T-Mobile') : print 'selected="selected"'; endif;?>>T-Mobile</option>
                    <option <?php if ($sms_provider === 'Tracfone') : print 'selected="selected"'; endif;?>>Tracfone</option>
                    <option <?php if ($sms_provider === 'U.S. Cellular') : print 'selected="selected"'; endif;?>>U.S. Cellular</option>
                    <option <?php if ($sms_provider === 'Verizon') : print 'selected="selected"'; endif;?>>Verizon</option>
                    <option <?php if ($sms_provider === 'Virgin Mobile') : print 'selected="selected"'; endif;?>>Virgin Mobile</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>
