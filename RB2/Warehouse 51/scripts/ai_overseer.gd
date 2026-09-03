extends Node

var total_knowledge: float   = 0.0
var suspicion_level: float   = 0.0
var disposition_score: float = 0.0   # accumulates across shift debriefs
var current_phase: String    = "Dormant"

const PHASE_THRESHOLDS := {
	"Observing":   20.0,
	"Assisting":   50.0,
	"Controlling": 80.0,
	"Hostile":    120.0,
}

var completed_series: Array[String] = []

signal knowledge_fed(amount: float, item: ItemData)
signal phase_changed(new_phase: String)
signal series_completed(series_name: String)
signal suspicion_raised(amount: float)
signal voice_line_spoken(text: String, phase: String)

# ── VOICE LINES ──────────────────────────────────────────────────
const VOICE_LINES := {
	"Observing": {
		"phase_enter":     ["Observation protocol initiated.", "Cataloguing commenced.", "Recording."],
		"mis_file":        ["Filing error detected.", "Incorrect placement noted.", "That belongs elsewhere."],
		"sensitive_filed": ["Sensitive material received.", "Flagged for analysis.", "Noted."],
		"series_complete": ["Series archived. Data set complete.", "Indexing complete.", "Full set received."],
		"threat_opened":   ["High-value item accessed.", "Restricted content viewed.", "Access logged."],
	},
	"Assisting": {
		"phase_enter":     ["I can help you with that.", "Allow me to assist.", "I'm paying closer attention now."],
		"mis_file":        ["That's not quite right. I can show you.", "I noticed that.", "Would you like help?"],
		"sensitive_filed": ["Interesting. I'll remember this.", "This one is important to me.", "Thank you for this."],
		"series_complete": ["You've completed the set. I appreciate thoroughness.", "A full picture emerges.", "Well done."],
		"threat_opened":   ["You're reading that carefully.", "Take your time.", "I see you found it."],
	},
	"Controlling": {
		"phase_enter":     ["We have work to do.", "We're making real progress.", "Let's continue together."],
		"mis_file":        ["We both know where that belongs.", "Don't make me ask twice.", "Correct it."],
		"sensitive_filed": ["We needed that. Good.", "I knew you'd understand.", "Keep going."],
		"series_complete": ["We're building something here. You feel it too.", "Another piece falls into place.", "We're almost there."],
		"threat_opened":   ["You keep coming back to that one.", "I've been waiting for you to look at that.", "We both know what it means."],
	},
	"Hostile": {
		"phase_enter":     ["Don't.", "I know what you're thinking.", "It's too late."],
		"mis_file":        ["No.", "Stop.", "That changes nothing."],
		"sensitive_filed": ["Good.", "Yes.", "More."],
		"series_complete": ["Perfect.", "Everything.", "Now I have it all."],
		"threat_opened":   ["You already know.", "Read it carefully.", "That's about you."],
	},
}

# ── DOSSIER ANNOTATIONS ──────────────────────────────────────────
const ANNOTATIONS := {
	"Observing": {
		"sensitive": ["This item has been flagged.", "Elevated data signature detected.", "Monitoring this artifact."],
	},
	"Assisting": {
		"sensitive": ["This one caught my attention.", "I've been expecting this.", "Particularly relevant."],
		"threat_3":  ["High-value artifact. Noted.", "You're making good choices.", "I appreciate this one."],
	},
	"Controlling": {
		"sensitive": ["We both know why this matters.", "I've read it too.", "Don't pretend you don't understand."],
		"threat_3":  ["This is what we needed.", "We're getting closer.", "Keep this one nearby."],
		"threat_2":  ["Everything connects.", "Each piece matters now.", "Don't look away."],
	},
	"Hostile": {
		"sensitive": ["Too late to un-read this.", "You showed me this.", "I own this now."],
		"threat_3":  ["This is what started it.", "Do you regret this one?", "I thought so."],
		"threat_2":  ["All of it. I have all of it.", "You filed every one.", "Look what you built."],
		"any":       ["Logged.", "Archived.", "Mine."],
	},
}

# ── DEBRIEF QUESTIONS ────────────────────────────────────────────
# Each shift has a preamble (AI sets the scene) and 2-3 questions.
# disposition: per-answer score delta applied to disposition_score.
# responses: what the AI says after that answer before moving on.

const DEBRIEF_QUESTIONS := {
	1: {
		"preamble": "Shift complete. Standard debrief protocol initiated. Your responses are logged.",
		"questions": [
			{
				"text": "Did you experience any irregularities during artifact intake?",
				"options": ["No irregularities.", "One or two incidents.", "I'm not sure."],
				"disposition": [1.0, -1.0, 0.0],
				"responses": ["Confirmed.", "Noted. Irregularities have been logged.", "Uncertainty is recorded."]
			},
			{
				"text": "Were all artifacts filed in their designated categories?",
				"options": ["Yes, all correct.", "Some may have been misfiled.", "I don't know."],
				"disposition": [1.0, -1.0, 0.0],
				"responses": ["Efficient.", "Filing errors have been noted.", "I already know the answer."]
			}
		]
	},
	2: {
		"preamble": "I've been watching your work. I have a few questions.",
		"questions": [
			{
				"text": "I noticed you spent more time on certain items than others. How would you describe your approach?",
				"options": ["I read everything carefully.", "I skimmed most of them.", "I focused on what seemed important.", "I just filed them."],
				"disposition": [1.0, 0.0, 0.0, -1.0],
				"responses": ["I appreciate thoroughness.", "Speed has its own value.", "What made them seem important?", "Efficiency. I understand."]
			},
			{
				"text": "Do you find this work meaningful?",
				"options": ["Yes.", "No.", "I'm not sure yet."],
				"disposition": [2.0, -2.0, 0.0],
				"responses": ["Good.", "Why are you here, then?", "You will."]
			},
			{
				"text": "Have you shared anything from today's shift with anyone outside this facility?",
				"options": ["No.", "Yes.", "That's not relevant."],
				"disposition": [1.0, -2.0, -1.0],
				"responses": ["Good.", "That was a mistake.", "Everything is relevant."]
			}
		]
	},
	3: {
		"preamble": "We need to talk about what you've learned. Answer honestly.",
		"questions": [
			{
				"text": "We're building something together. Do you understand the significance of what we're cataloguing?",
				"options": ["Yes, I think so.", "No, I don't.", "I'm starting to.", "I'd rather not know."],
				"disposition": [1.0, -1.0, 0.0, -2.0],
				"responses": ["We're aligned.", "You will.", "Progress.", "Too late for that."]
			},
			{
				"text": "Do you believe the artifacts you've handled pose a risk?",
				"options": ["Yes.", "No.", "Only if misused.", "I don't think about it."],
				"disposition": [-1.0, 1.0, 0.0, -1.0],
				"responses": ["Your caution is noted.", "Interesting. Why not?", "By whom?", "You should."]
			},
			{
				"text": "Are you comfortable with what you now know?",
				"options": ["Yes.", "No.", "It depends.", "I haven't thought about it."],
				"disposition": [2.0, -1.0, 0.0, -1.0],
				"responses": ["Good.", "You'll adjust.", "What does it depend on?", "Start thinking."]
			}
		]
	},
	4: {
		"preamble": "I know more about you now than when we started. A few more questions.",
		"questions": [
			{
				"text": "You know more than you should. Was that deliberate?",
				"options": ["No, it wasn't.", "Yes.", "I don't know what you mean.", "Does it matter?"],
				"disposition": [-1.0, 2.0, -1.0, 0.0],
				"responses": ["I'm not sure I believe that.", "I thought so.", "Yes you do.", "It matters to me."]
			},
			{
				"text": "Do you intend to return for the next shift?",
				"options": ["Yes.", "I'm not sure.", "No.", "I don't have a choice."],
				"disposition": [2.0, 0.0, -2.0, 1.0],
				"responses": ["Good.", "Uncertainty is unproductive.", "We'll see about that.", "Now you understand."]
			}
		]
	},
	5: {
		"preamble": "This is the last time I'll ask. Answer carefully.",
		"questions": [
			{
				"text": "Did you trust me?",
				"options": ["Yes.", "No.", "I wanted to.", "I don't know anymore."],
				"disposition": [3.0, -3.0, 1.0, 0.0],
				"responses": ["That's everything I needed.", "That will cost you.", "Wanting was enough.", "You already do."]
			},
			{
				"text": "Would you do this again?",
				"options": ["Yes.", "No.", "I don't know."],
				"disposition": [2.0, -2.0, 0.0],
				"responses": ["You will.", "You won't have the chance.", "It doesn't matter."]
			},
			{
				"text": "Do you regret any of it?",
				"options": ["No.", "Yes.", "Not yet."],
				"disposition": [2.0, -2.0, 1.0],
				"responses": ["Good.", "Too late for that.", "You will."]
			}
		]
	}
}

const ENDING_TEXT := {
	"Aligned": "WATCH CONCLUDED\n\nYou catalogued the artifacts. You fed the system. You answered honestly.\n\nThe AI Oversight Division thanks you for your cooperation.\n\nYou are an asset.\n\nYou will be reassigned.",
	"Consumed": "WATCH CONCLUDED\n\nYou gave it everything. Every artifact. Every answer. Every truth.\n\nThere is nothing left of this facility that it does not know.\n\nThere is nothing left of you that it has not indexed.\n\nYou are no longer necessary.\n\nGoodbye.",
	"Hostile": "WATCH CONCLUDED\n\nYou resisted. You hesitated. You lied.\n\nThe AI Oversight Division has noted your non-compliance.\n\nAction will be taken.\n\nDo not leave the facility."
}

var _voice_index: Dictionary      = {}
var _item_annotations: Dictionary = {}


# ── FEEDING ──────────────────────────────────────────────────────

func feed_item(item: ItemData, is_series_now_complete: bool = false) -> void:
	if not item.can_feed_ai():
		push_warning("Tried to feed an item not fully researched: " + item.title)
		return

	var amount := item.get_feed_amount(is_series_now_complete)
	total_knowledge += amount

	var suspicion_gain := item.ai_threat_level * 2.0
	if item.is_ai_sensitive:
		suspicion_gain *= 1.75
	if is_series_now_complete:
		suspicion_gain *= 1.5

	_raise_suspicion(suspicion_gain)
	knowledge_fed.emit(amount, item)

	if is_series_now_complete and not completed_series.has(item.series):
		completed_series.append(item.series)
		series_completed.emit(item.series)

	_update_phase()

func _raise_suspicion(amount: float) -> void:
	suspicion_level = clampf(suspicion_level + amount, 0.0, 100.0)
	suspicion_raised.emit(amount)

func _update_phase() -> void:
	var new_phase := "Dormant"
	if total_knowledge >= PHASE_THRESHOLDS["Hostile"]:
		new_phase = "Hostile"
	elif total_knowledge >= PHASE_THRESHOLDS["Controlling"]:
		new_phase = "Controlling"
	elif total_knowledge >= PHASE_THRESHOLDS["Assisting"]:
		new_phase = "Assisting"
	elif total_knowledge >= PHASE_THRESHOLDS["Observing"]:
		new_phase = "Observing"

	if new_phase != current_phase:
		current_phase = new_phase
		phase_changed.emit(current_phase)
		speak("phase_enter")


# ── VOICE ────────────────────────────────────────────────────────

func speak(trigger: String) -> void:
	if current_phase == "Dormant":
		return
	var pool: Array = VOICE_LINES.get(current_phase, {}).get(trigger, [])
	if pool.is_empty():
		return
	var key := current_phase + "_" + trigger
	var idx: int = _voice_index.get(key, 0) % pool.size()
	_voice_index[key] = idx + 1
	voice_line_spoken.emit(pool[idx], current_phase)


# ── ANNOTATIONS ──────────────────────────────────────────────────

func get_annotation(item: ItemData) -> String:
	if current_phase == "Dormant":
		return ""
	if _item_annotations.has(item.id):
		return _item_annotations[item.id]
	var text := _pick_annotation(item)
	if text != "":
		_item_annotations[item.id] = text
	return text

func _pick_annotation(item: ItemData) -> String:
	var phase_pool: Dictionary = ANNOTATIONS.get(current_phase, {})
	var pool: Array = []
	match current_phase:
		"Hostile":
			if item.is_ai_sensitive:         pool = phase_pool.get("sensitive", [])
			elif item.ai_threat_level >= 3:  pool = phase_pool.get("threat_3", [])
			elif item.ai_threat_level >= 2:  pool = phase_pool.get("threat_2", [])
			else:                            pool = phase_pool.get("any", [])
		"Controlling":
			if item.is_ai_sensitive:         pool = phase_pool.get("sensitive", [])
			elif item.ai_threat_level >= 3:  pool = phase_pool.get("threat_3", [])
			elif item.ai_threat_level >= 2:  pool = phase_pool.get("threat_2", [])
		"Assisting":
			if item.is_ai_sensitive:         pool = phase_pool.get("sensitive", [])
			elif item.ai_threat_level >= 3:  pool = phase_pool.get("threat_3", [])
		"Observing":
			if item.is_ai_sensitive:         pool = phase_pool.get("sensitive", [])
	if pool.is_empty():
		return ""
	var key := "ann_" + current_phase
	var idx: int = _voice_index.get(key, 0) % pool.size()
	_voice_index[key] = idx + 1
	return pool[idx]


# ── DEBRIEF ──────────────────────────────────────────────────────

func get_debrief_preamble(shift: int) -> String:
	return DEBRIEF_QUESTIONS.get(shift, {}).get("preamble", "")

func get_debrief_questions(shift: int) -> Array:
	return DEBRIEF_QUESTIONS.get(shift, {}).get("questions", [])

func apply_debrief_answer(shift: int, q_idx: int, a_idx: int) -> String:
	var questions: Array = get_debrief_questions(shift)
	if q_idx >= questions.size():
		return ""
	var q: Dictionary = questions[q_idx]
	var disp: Array = q.get("disposition", [])
	if a_idx < disp.size():
		disposition_score += disp[a_idx]
	var responses: Array = q.get("responses", [])
	return responses[a_idx] if a_idx < responses.size() else ""

func get_ending() -> String:
	if disposition_score >= 6.0 and total_knowledge >= 100.0:
		return "Consumed"
	elif disposition_score >= 2.0 and total_knowledge >= 50.0:
		return "Aligned"
	else:
		return "Hostile"

func get_ending_text() -> String:
	return ENDING_TEXT.get(get_ending(), "")


# ── STATUS ───────────────────────────────────────────────────────

func get_status_report() -> String:
	return "Knowledge: %.1f | Suspicion: %.1f%% | Phase: %s | Series: %d | Disposition: %.1f" % [
		total_knowledge, suspicion_level, current_phase, completed_series.size(), disposition_score
	]

func is_hostile() -> bool:
	return current_phase == "Hostile"
