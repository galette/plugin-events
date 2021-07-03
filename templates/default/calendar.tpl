{extends file="page.tpl"}
{block name="content"}
    <div id='calendar'></div>
{/block}

{block name="javascripts"}
<script type="text/javascript">
    var _calendar_dataurl = '{path_for name="ajax-events_calendar"}';
    var _calendar_event_url = '{path_for name="events_event_edit" data=["id" => "PLACEBO"]}';
    var _fullcalendar_views = {
      listDay: { buttonText: '{_T string="Daily list" domain="events" escape="js"}' },
      listWeek: { buttonText: '{_T string="Weekly list" domain="events" escape="js"}' },
      listMonth: { buttonText: '{_T string="Monthly list" domain="events" escape="js"}' },
      dayGridMonth: { buttonText: '{_T string="Month calendar" domain="events" escape="js"}' },
      today: { buttonText: '{_T string="Today" domain="events" escape="js"}' }
    }
    var _fullcalendar_locale = '{$galette_lang}';
    $(function() {
        {include file="js_removal.tpl"}
    });
</script>
<script type="text/javascript" src="{path_for name="plugin_res" data=["plugin" => $module_id, "path" => "js/calendar.bundle.js"]}"></script>
{/block}
