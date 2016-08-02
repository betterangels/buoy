<p>
    <label>
        <input type="checkbox"
            id="sms_email_bridge_enabled"
            name="sms_email_bridge_enabled"
            value="1"
            <?php checked($post->sms_email_bridge_enabled);?>
        /> <?php esc_html_e('Enable team SMS/txt chat', 'buoy');?>
    </label>
</p>
<p class="description">
    <?php esc_html_e('Enabling SMS/txt chat for a team creates an always-on channel for confirmed team members to broadcast a txt messages to everyone else on this team by sending a single txt message to a specially-configured email address. TXT messages sent to the email address configured below will be automatically forwarded to every confirmed team member.');?>
</p>
<details open="open">
    <summary><?php _e('SMS &larr;&rarr; Email bridge configuration', 'buoy');?></summary>
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <label for="sms_email_bridge_address">
                        <?php esc_html_e('Email address', 'buoy');?><br />
                        <?php esc_html_e('(DO NOT USE YOUR PERSONAL EMAIL ADDRESS)', 'buoy');?>
                    </label>
                </th>
                <td>
                    <input type="email"
                        class="code large-text"
                        name="sms_email_bridge_address"
                        id="sms_email_bridge_address"
                        placeholder="NOT_YOUR_PERSONAL_ADDRESS@gmail.com"
                        value="<?php esc_attr_e($post->sms_email_bridge_address);?>"
                    />
                    <span class="description">
                        <?php esc_html_e('Enter the email address used to relay inbound messages.', 'buoy');?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Username', 'buoy');?>
                </th>
                <td>
                    <input type="text"
                        class="code large-text"
                        name="sms_email_bridge_username"
                        id="sms_email_bridge_username"
                        placeholder="username"
                        value="<?php esc_attr_e($post->sms_email_bridge_username);?>"
                    />
                    <span class="description">
                        <?php esc_html_e('Enter the email account username used to retrieve emails.', 'buoy');?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Password', 'buoy');?>
                </th>
                <td>
                    <input type="text"
                        class="code large-text"
                        name="sms_email_bridge_password"
                        id="sms_email_bridge_password"
                        placeholder="password"
                        value="<?php esc_attr_e($post->sms_email_bridge_password);?>"
                    />
                    <span class="description">
                        <?php esc_html_e('Enter the password for the email account.', 'buoy');?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Incoming (IMAP) Mail Server', 'buoy');?>
                </th>
                <td>
                    <input type="text"
                        class="code large-text"
                        name="sms_email_bridge_server"
                        id="sms_email_bridge_server"
                        placeholder="imap.gmail.com"
                        value="<?php esc_attr_e($post->sms_email_bridge_server);?>"
                    />
                    <span class="description">
                        <?php esc_html_e('Enter the email server domain name or IP address.', 'buoy');?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('IMAP Port Number', 'buoy');?>
                </th>
                <td>
                    <input type="number" min="1" max="65535"
                        class="code small-text"
                        name="sms_email_bridge_port"
                        id="sms_email_bridge_port"
                        placeholder="995"
                        value="<?php esc_attr_e($post->sms_email_bridge_port);?>"
                    />
                    <span class="description">
                        <?php esc_html_e('Enter the port number on which the email server is listening for connections.', 'buoy');?>
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</details>
