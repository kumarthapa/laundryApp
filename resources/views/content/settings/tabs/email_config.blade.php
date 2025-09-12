<div class="card mb-3">
    <div class="card-body">
        <h4 class="card-header mb-3 px-0 py-2">Email Configuration</h4>
        <form class="needs-validation save_setting_data py-2" novalidate id="save_email_configuration">
            {{ csrf_field() }}
            <input type="hidden" name="submit_form_name" value="save_email_configuration">
            <div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_MAILER]" value="MAIL_MAILER">
                    <input type="hidden" name="setting_key_name[MAIL_MAILER]" value="MAIL MAILER">
                    <p for="MAIL MAILER">MAIL MAILER</p>
                    <input type="text" id="MAIL_MAILER" name="value[MAIL_MAILER]" class="form-control"
                        placeholder="Enter Mail Mailer" value="{{ $UtilityHelper->getConfigValue("MAIL_MAILER") }}">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_HOST]" value="MAIL_HOST">
                    <input type="hidden" name="setting_key_name[MAIL_HOST]" value="MAIL HOST">
                    <p for="MAIL HOST">MAIL HOST</p>
                    <input type="text" id="MAIL_HOST" name="value[MAIL_HOST]" class="form-control"
                        placeholder="Enter Mail Host" value="{{ $UtilityHelper->getConfigValue("MAIL_HOST") }}">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_PORT]" value="MAIL_PORT">
                    <input type="hidden" name="setting_key_name[MAIL_PORT]" value="MAIL PORT">
                    <p for="MAIL PORT">MAIL PORT</p>
                    <input type="text" id="MAIL_PORT" name="value[MAIL_PORT]" class="form-control"
                        placeholder="Enter Mail Port" value="{{ $UtilityHelper->getConfigValue("MAIL_PORT") }}">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_USERNAME]" value="MAIL_USERNAME">
                    <input type="hidden" name="setting_key_name[MAIL_USERNAME]" value="MAIL USERNAME">
                    <p for="MAIL USERNAME">MAIL USERNAME</p>
                    <input type="text" id="MAIL_USERNAME" name="value[MAIL_USERNAME]" class="form-control"
                        placeholder="Enter Mail Username" value="{{ $UtilityHelper->getConfigValue("MAIL_USERNAME") }}">
                </div>
                <div class="mb-3">


                    {{-- <div class="input-group input-group-merge has-validation">
                        <input class="form-control" type="text" id="newPassword" name="newPassword" placeholder="············" fdprocessedid="hnpgk">
                        <span class="input-group-text cursor-pointer"><i class="bx bx-show"></i></span>
                      </div> --}}




                    <input type="hidden" name="setting_key[MAIL_PASSWORD]" value="MAIL_PASSWORD">
                    <input type="hidden" name="setting_key_name[MAIL_PASSWORD]" value="MAIL PASSWORD">
                    <p for="MAIL PASSWORD">MAIL PASSWORD</p>
                    <input type="text" id="MAIL_PASSWORD" name="value[MAIL_PASSWORD]" class="form-control"
                        placeholder="Enter Mail Password" value="{{ $UtilityHelper->getConfigValue("MAIL_PASSWORD") }}">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_ENCRYPTION]" value="MAIL_ENCRYPTION">
                    <input type="hidden" name="setting_key_name[MAIL_ENCRYPTION]" value="MAIL ENCRYPTION">
                    <p for="MAIL ENCRYPTION">MAIL ENCRYPTION</p>
                    <input type="text" id="MAIL_ENCRYPTION" name="value[MAIL_ENCRYPTION]" class="form-control"
                        placeholder="Enter Mail Encryption"
                        value="{{ $UtilityHelper->getConfigValue("MAIL_ENCRYPTION") }}">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_FROM_ADDRESS]" value="MAIL_FROM_ADDRESS">
                    <input type="hidden" name="setting_key_name[MAIL_FROM_ADDRESS]" value="MAIL FROM ADDRESS">
                    <p for="MAIL FROM ADDRESS">MAIL FROM ADDRESS</p>
                    <input type="text" id="MAIL_FROM_ADDRESS" name="value[MAIL_FROM_ADDRESS]"
                        class="form-control" placeholder="Enter Mail From Address"
                        value="{{ $UtilityHelper->getConfigValue("MAIL_FROM_ADDRESS") }}">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="setting_key[MAIL_FROM_NAME]" value="MAIL_FROM_NAME">
                    <input type="hidden" name="setting_key_name[MAIL_FROM_NAME]" value="MAIL FROM NAME">
                    <p for="MAIL FROM NAME">MAIL FROM NAME</p>
                    <input type="text" id="MAIL_FROM_NAME" name="value[MAIL_FROM_NAME]" class="form-control"
                        placeholder="Enter Mail From Name"
                        value="{{ $UtilityHelper->getConfigValue("MAIL_FROM_NAME") }}">
                </div>
            </div>
            <div class="py-2 text-end">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>

        </form>
    </div>
</div>
