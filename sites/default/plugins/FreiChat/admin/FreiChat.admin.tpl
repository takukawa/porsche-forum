{if $flash['flash']==true}
    <div class="col-md-8">
        <div class="alert alert-success">
            {$flash['message']}
        </div>
    </div>
{/if}

<style type="text/css">

    legend {
        padding-top: 10px;
    }

    .label-note {
        font-size: 11px;
        color: #666;
        padding-left: 2px;
        font-weight: bold;
    }
</style>
<div class="col-md-6">
    <div>

        <form action="index.php?page=ploader&plugin=FreiChat" role="form" method="post" enctype="multipart/form-data">

            <div class="box box-info">
                <fieldset class="box-body">
                    <legend>FreiChat Settings</legend>
                    <label>FreiChat token</label>
                    <input type="text" class="form-control" name="FREICHAT_APP_KEY"
                           value="{"FREICHAT_APP_KEY"|get_opt}"/>
                    <div class="label-note">Please do not change the secret key unless you are told to do so.</div>

                    <br/>

                    <label>Enable floating chat</label>
                    {html_options name=FREICHAT_FLOAT_ENABLED options=$float_enabled_options selected=$float_enabled_selected class="form-control"}

                    <br/>
                    <input type="hidden" name="CSRF_token" value="{$token}"/>
                    <input type="submit" value="Save" class="btn btn-primary"/>
                    <a target="_blank" class="btn btn-success" href="https://app.freichat.com/">Manage FreiChat</a>
                    <br/>
                    <div class="label-note">Note: When you open "Manage FreiChat", you may have to register if you don't have an account already. <br/>
                        After login, you can add an existing site using above FreiChat token</div>


                </fieldset>
            </div>



        </form>
        <br/>
        <br/>


    </div>
