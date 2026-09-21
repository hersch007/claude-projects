const assert = require('node:assert/strict');

function gameChange(teamA, teamB, scoreA, scoreB, averageGames = 0) {
  const expected = 1 / (1 + Math.pow(10, (teamB - teamA) / 400));
  const aWon = scoreA > scoreB;
  const winnerScore = Math.max(scoreA, scoreB);
  const margin = Math.abs(scoreA - scoreB);
  const winnerPerformance = 0.75 + 0.25 * (margin / winnerScore);
  const actual = aWon ? winnerPerformance : 1 - winnerPerformance;
  const k = averageGames < 8 ? 32 : averageGames < 25 ? 24 : 16;
  const changeA = Math.round(k * (actual - expected));
  return { changeA, changeB: -changeA, expected, actual, k };
}

function recalculate(games) {
  const ratings = {};
  const counts = {};
  for (const game of games) {
    const names = [...game.a, ...game.b];
    for (const name of names) {
      if (!(name in ratings)) ratings[name] = 1200;
      if (!(name in counts)) counts[name] = 0;
    }
    const teamA = (ratings[game.a[0]] + ratings[game.a[1]]) / 2;
    const teamB = (ratings[game.b[0]] + ratings[game.b[1]]) / 2;
    const averageGames = names.reduce((sum, name) => sum + counts[name], 0) / 4;
    const result = gameChange(teamA, teamB, game.sa, game.sb, averageGames);
    for (const name of game.a) { ratings[name] += result.changeA; counts[name]++; }
    for (const name of game.b) { ratings[name] += result.changeB; counts[name]++; }
  }
  return ratings;
}

const equalClose = gameChange(1200, 1200, 11, 9);
assert.equal(equalClose.changeA, 9, 'An even 11–9 game should move 9 points');
assert.equal(equalClose.changeA + equalClose.changeB, 0, 'Every change must be zero-sum');

const equalBlowout = gameChange(1200, 1200, 11, 1);
assert.ok(equalBlowout.changeA > equalClose.changeA, 'A decisive win must move more than a close win');

const upset = gameChange(1100, 1300, 11, 9);
const expectedWin = gameChange(1300, 1100, 11, 9);
assert.ok(upset.changeA > equalClose.changeA, 'An upset must reward more than an even matchup');
assert.ok(expectedWin.changeA < equalClose.changeA, 'An expected favorite win must reward less');

assert.equal(gameChange(1200, 1200, 11, 9, 0).k, 32, 'Early ratings use K=32');
assert.equal(gameChange(1200, 1200, 11, 9, 8).k, 24, 'Established ratings use K=24');
assert.equal(gameChange(1200, 1200, 11, 9, 25).k, 16, 'Mature ratings use K=16');

const games = [
  { a: ['Richard', 'Tara'], b: ['Rob', 'Karen'], sa: 11, sb: 5 },
  { a: ['Richard', 'Karen'], b: ['Rob', 'Tara'], sa: 6, sb: 11 },
  { a: ['Richard', 'Rob'], b: ['Karen', 'Tara'], sa: 11, sb: 7 },
];
const original = recalculate(games);
const afterDelete = recalculate([games[0], games[2]]);
assert.notDeepEqual(afterDelete, original, 'Deleting a game must recalculate standings');
assert.deepEqual(recalculate(games), original, 'Restoring the game must restore the same ratings');

const edited = recalculate([games[0], { ...games[1], sa: 11, sb: 6 }, games[2]]);
assert.notDeepEqual(edited, original, 'Editing a result must recalculate ratings');

console.log('Rating model: 10 regression checks passed.');
