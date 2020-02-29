
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
</style>
<div class="col-md-6">
    <div>

        <form action="index.php?page=ploader&plugin=AntiSpamRules" role="form" method="post" enctype="multipart/form-data">

            <div class="box box-info">
                <fieldset class="box-body">
                    <legend>Bot Blocking Strategy</legend>
                    <label>Words to block (comma separated)</label>
                    <input type="text" class="form-control" name="ASR_BLOCK_TILL_POST_COUNT_WORDS" value="{"ASR_BLOCK_TILL_POST_COUNT_WORDS"|get_opt}" /><br/>

                    <label>Block the above words till the user's post count is:</label>
                    <input type="number" class="form-control" name="ASR_BLOCK_TILL_POST_COUNT" value="{"ASR_BLOCK_TILL_POST_COUNT"|get_opt}" /><br/>
                </fieldset>
            </div>

            <div class="box box-info">
                <fieldset class="box-body">
                    <label>Permanently Banned Words (comma separated)</label>
                    <input type="text" class="form-control" name="ASR_WORDS_NOT_ALLOWED_EVER" value="{"ASR_WORDS_NOT_ALLOWED_EVER"|get_opt}" /><br/>
                </fieldset>
            </div>



            <input type="hidden" name="CSRF_token" value="{$token}" />
            <input type="submit" value="Save" class="btn btn-primary"/>
        </form>
        <br/>
        <br/>
    </div>
</div>