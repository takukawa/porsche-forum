{* @CODOLICENSE *}
{* Smarty *}
{extends file='layout.tpl'}
{block name=body}
    <style type="text/css">
        .container {
            padding-top: 50px;
        }

        .freichat-loader {
            text-align: center;
            margin-top: 10%;
        }
    </style>
    <div class="container">


        <div class="row">


        </div>
    </div>
    <div id="FreiChatRootMountDiv">
        <div class="freichat-loader">
            <img src="{$smarty.const.DURI}plugins/FreiChat/assets/img/loader.gif" alt="loading freichat" />
            <div>{_t("Loading FreiChat assets...")}</div>
        </div>
    </div>
{/block}