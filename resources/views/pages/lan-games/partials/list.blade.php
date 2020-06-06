<script type="application/javascript">
    function toggleVote(lan_game_id) {
        const checkbox = document.getElementById('lan_game_' + lan_game_id + '_checkbox')
        const row = document.getElementById('lan_game_' + lan_game_id + '_row')
        const form = document.getElementById('lan_game_' + lan_game_id + '_form')
        if (checkbox.checked) {
            checkbox.checked = false
            row.classList.remove("bg-primary")
        } else {
            checkbox.checked = true
            row.classList.add("bg-primary")
        }
        form.submit()
    }
</script>
<table class="table table-striped">
    <tbody>
    @foreach($lanGames as $lanGame)
        @php
            $vote = $lanGame->votes->where('user_id',Auth::user()->id)->first();
            if($vote != null) {
                $voted = true;
                $route = route('lans.lan-games.votes.destroy', ['lan' => $lanGame->lan, 'lan_game' => $lanGame, 'vote' => $vote]);
            } else {
                $voted = false;
                $route = route('lans.lan-games.votes.store', ['lan' => $lanGame->lan, 'lan_game' => $lanGame]);
            }
        @endphp
        <tr class="{{ $voted ? 'bg-primary' : '' }}"
            onclick="toggleVote({{ $lanGame->id }})"
            id="lan_game_{{ $lanGame->id }}_row">
            <td>
                <form id="lan_game_{{ $lanGame->id }}_form" method="POST" action="{{ $route }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="lan_game_id" value="{{ $lanGame->id }}">
                    @if($voted)
                        {{ method_field('DELETE') }}
                    @endif
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox"
                               class="custom-control-input"
                               id="lan_game_{{ $lanGame->id }}_checkbox"
                            {{ $voted ? 'checked' : '' }}
                        >
                        <label class="custom-control-label" for="lan_game_{{ $lanGame->id }}_checkbox">
                            {{ $lanGame->game_name }}
                        </label>
                    </div>
                </form>
            </td>
            <td>
                @foreach($lanGame->votes as $vote)
                    @include('pages.users.partials.avatar', ['user' => $vote->user])
                @endforeach
            </td>
            <td class="text-right pr-0">
                @canany(['update', 'delete'], $lanGame)
                    @include('pages.lan-games.partials.actions-dropdown', ['lanGame' => $lanGame])
                @endcanany
            </td>
        </tr>
    @endforeach
    </tbody>
</table>