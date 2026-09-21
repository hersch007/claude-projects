// A no-disk storage adapter for tests. Without this, runAudit() falls
// back to fileStorage and writes score-history.json/keyword-history.json
// into whatever the CWD happens to be when the test runs — real repo
// pollution, not just a cosmetic issue, since a stray score-history.json
// looks exactly like real client data if it ever got committed by
// accident. Every test in this suite passes `{ storage: memoryStorage() }`
// instead.
function memoryStorage() {
  const history = new Map();
  const keywordHistory = new Map();
  return {
    loadHistory: async (client) => history.get(client.name) || [],
    saveHistory: async (client, h) => { history.set(client.name, h); },
    loadKeywordHistory: async (client) => keywordHistory.get(client.name) || [],
    saveKeywordHistory: async (client, date, keywords) => {
      const list = keywordHistory.get(client.name) || [];
      list.push({ date, keywords });
      keywordHistory.set(client.name, list);
    },
    loadMetrics: async () => null,
  };
}

module.exports = { memoryStorage };
