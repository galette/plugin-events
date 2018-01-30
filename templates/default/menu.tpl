        <h1 class="nojs">{_T string="Events" domain="events"}</h1>
        <ul>
            <li{if $cur_route eq "events_events"} class="selected"{/if}><a href="{path_for name="events_events"}">{_T string="Events" domain="events"}</a></li>
{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
            <li{if $cur_route eq "events_bookings"} class="selected"{/if}><a href="{path_for name="events_bookings"}">{_T string="Bookings" domain="events"}</a></li>
{/if}
        </ul>
