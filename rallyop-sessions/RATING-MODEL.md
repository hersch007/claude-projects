# RallyOP rating model

RallyOP uses a transparent, recreational doubles rating inspired by Elo and DUPR-style principles. It is not an official DUPR rating.

## Plain-language explanation

Every player starts at 1200. Before each game, RallyOP averages the two players' ratings on each team to estimate the expected result. The actual result includes both who won and the final-score margin.

- Beating a stronger team moves a rating more than beating a weaker team.
- A decisive result moves ratings more than a close result.
- Early ratings move faster while the system learns a player's level.
- Ratings become steadier after 8 and again after 25 results.
- Each winner's gain is matched by each loser's loss, so simply playing more games does not create free rating points.

## Calculation

1. Average each team's two player ratings.
2. Calculate Team A's expected result with the Elo expectation formula:

   `expected = 1 / (1 + 10 ^ ((team_b - team_a) / 400))`

3. Convert the score into a performance result:

   - Winner: `0.75 + 0.25 × (winning margin / winner's score)`
   - Loser: the complementary value

4. Apply a symmetric change:

   `change = round(K × (performance - expected))`

   `K` is 32 for the first 8 average results in the matchup, 24 through 24 results, and 16 afterward.

Both winning players receive the same change and both losing players receive its opposite. Ratings are recalculated chronologically from recorded games, so correcting or deleting a result also corrects the standings.

