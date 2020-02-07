// elements in the DOM
const scoreDiv = document.querySelector(".score_div");
const gameSelect = document.querySelector("select[name='game_id']");
const homeTeamInput = document.querySelector("input[name='home_team_prediction']");
const homeTeamLabel = homeTeamInput.previousElementSibling;
const awayTeamInput = document.querySelector("input[name='away_team_prediction']");
const awayTeamLabel = awayTeamInput.previousElementSibling;

// when the game dropdown is changed
gameSelect.addEventListener('change', function() {

    // get selected game
    let selectedGameId = this.value;

    // turn object into array
    let teamsToDisplay = Object.entries(teamsInGames[selectedGameId]);

    // show the score div
    scoreDiv.classList.remove('hidden');

    // set inputs to nothing
    homeTeamInput.value = "";
    awayTeamInput.value = "";

    // set text of input labels to team names
    homeTeamLabel.innerHTML = teamsToDisplay[0][1];
    awayTeamLabel.innerHTML = teamsToDisplay[1][1];
});