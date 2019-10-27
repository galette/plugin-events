{extends file="page.tpl"}
{block name="content"}
        <div id="calendar"</div>
{/block}

{block name="javascripts"}
<script type="text/javascript">
    var _calendar_dataurl = '{path_for name="ajax-events_calendar"}';
    var _fullcalendar_views = {
      listDay: { buttonText: '{_T string='Daily list' escape="js"}' },
      listWeek: { buttonText: '{_T string="Weekly list" escape="js"}' },
      listMonth: { buttonText: '{_T string='Monthly list' escape="js"}' },
      dayGridMonth: { buttonText: '{_T string='Month calendar' escape="js"}' },
      today: { buttonText: '{_T string="Today"}' }
    }
    var _fullcalendar_locale = '{$galette_lang}';
    $(function() {
        {include file="js_removal.tpl"}
    });
</script>
<script type="text/javascript" src="{path_for name="plugin_res" data=["plugin" => $module_id, "path" => "js/calendar.bundle.js"]}"></script>
{/block}
