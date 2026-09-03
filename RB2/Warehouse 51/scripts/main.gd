extends Node2D

# ── PALETTE ──────────────────────────────────────────────────────
const C_PANEL   := Color(0.052, 0.038, 0.025)   # dark walnut panel
const C_BORDER  := Color(0.220, 0.145, 0.072)   # warm oak border
const C_TEXT    := Color(0.820, 0.748, 0.630)   # aged parchment
const C_DIM     := Color(0.420, 0.318, 0.218)   # dim aged ink
const C_GREEN   := Color(0.000, 0.910, 0.494)
const C_AMBER   := Color(1.000, 0.620, 0.100)
const C_RED     := Color(0.950, 0.130, 0.240)
const C_HEADER  := Color(0.900, 0.828, 0.700)   # warm parchment header

# ── SHELF ROW → CATEGORY ─────────────────────────────────────────
const ROW_CATEGORIES: Array[String] = [
	"Intelligence", "Research", "Field Report", "Technical", "Biological"
]
const TRAY_DELAY: float = 10.0

# ── SHIFT SYSTEM ─────────────────────────────────────────────────
const SHIFT_COUNT:          int   = 5
const ARTIFACTS_PER_SHIFT:  int   = 8
const SHIFT_DURATION:       float = 420.0   # 7 minutes per shift

# ── AUTOLOADS ────────────────────────────────────────────────────
var ai_overseer
var research_manager

# ── ITEM TRACKING ────────────────────────────────────────────────
var item_nodes: Dictionary = {}
var node_to_item: Dictionary = {}
var item_layout: Dictionary = {}
var _row_left_counts:  Array[int] = [0, 0, 0, 0, 0]
var _row_right_counts: Array[int] = [0, 0, 0, 0, 0]
var selected_item: ItemData = null
var selected_node = null

# ── TRAY / DELIVERY STATE ────────────────────────────────────────
var _incoming_queue: Array[ItemData] = []
var _current_tray_item: ItemData     = null
var _tray_waiting: bool              = false
var _tray_timer: float               = 0.0
var _wrong_filed: Dictionary         = {}   # item.id -> true when mis-filed

# ── SHIFT STATE ───────────────────────────────────────────────────
var _current_shift:         int   = 1
var _artifacts_this_shift:  int   = 0
var _shift_timer:           float = SHIFT_DURATION
var _shift_active:          bool  = false

# ── DRAG STATE ───────────────────────────────────────────────────
var _dragging_tray: bool       = false
var _drag_offset: Vector2      = Vector2.ZERO
var _drag_start_pos: Vector2   = Vector2.ZERO
const ARTIFACT_HOME: Vector2   = Vector2(1048.0, 552.0)  # lower-right, floor level

# ── 2D UI REFS ───────────────────────────────────────────────────
var item_layer: CanvasLayer
var _item_root: Control = null
var _bg_node: Node2D

var phase_label: Label
var knowledge_label: Label
var suspicion_label: Label
var shift_label: Label
var timer_label: Label
var notification_label: Label
var notification_timer: float = 0.0

# Dossier
var dossier_panel: PanelContainer
var d_title: Label
var d_classify: Label
var d_meta: Label
var d_lore: RichTextLabel
var d_research_bar: ProgressBar
var d_research_stage: Label
var d_ai_sensitivity: Label
var d_research_btn: Button
var d_ai_annotation: Label

# Debrief panel
var debrief_root: Control                  = null
var debrief_panel: PanelContainer          = null
var _db_shift_lbl: Label                   = null
var _db_preamble_lbl: Label                = null
var _db_progress_lbl: Label                = null
var _db_question_lbl: Label                = null
var _db_answers_box: VBoxContainer         = null
var _db_response_lbl: Label                = null
var _db_proceed_btn: Button                = null
var _db_questions: Array                   = []
var _db_q_idx: int                         = 0
var _db_response_timer: float              = 0.0

# Tray artifact (the draggable physical object)
var tray_artifact: Control          = null
var _artifact_tex_rect: TextureRect = null
var _art_banner_lbl: Label          = null
var _art_title_lbl: Label           = null
var _art_cat_lbl: Label             = null
var tray_hint_lbl: Label            = null
var tray_next_lbl: Label           = null
var _row_overlays: Array[ColorRect] = []
var _filing_hint_overlay: Label = null

# ── SCROLL STATE ─────────────────────────────────────────────────
var scroll_offset: float = 0.0
const SCROLL_SPEED: float = 650.0

# ── SHELF LAYOUT (must match warehouse_bg.gd) ────────────────────
const LEFT_SHELF_X:    float = 150.0
const RIGHT_SHELF_X:   float = 1130.0
const SHELF_ROW_Y: Array[float] = [143.0, 232.0, 328.0, 425.0, 504.0]
const SHELF_ROWS:  int          = 5
const ITEM_W:       float = 34.0
const ITEM_H:       float = 52.0
const ITEM_SPACING: float = 40.0


# ════════════════════════════════════════════════════════════════
func _ready() -> void:
	Input.mouse_mode = Input.MOUSE_MODE_VISIBLE
	ai_overseer      = get_node("/root/AIOverseer")
	research_manager = get_node("/root/ResearchManager")
	_build_background()
	_build_item_layer()
	_build_row_overlays()
	_build_ui()
	_connect_signals()
	_populate_queue()
	_shift_active = true
	_deliver_next_item()

func _process(delta: float) -> void:
	if notification_timer > 0.0:
		notification_timer -= delta
		if notification_timer <= 0.0:
			notification_label.visible = false

	if _shift_active:
		_shift_timer -= delta
		_refresh_shift_hud()
		if _shift_timer <= 0.0:
			_end_shift()

	if _db_response_timer > 0.0:
		_db_response_timer -= delta
		if _db_response_timer <= 0.0:
			_db_q_idx += 1
			if _db_q_idx >= _db_questions.size():
				_finish_debrief()
			else:
				_show_db_question()

	var move := Input.get_axis("ui_left", "ui_right")
	if move != 0.0:
		_scroll(move * SCROLL_SPEED * delta)

	# Drag: move artifact with cursor
	if _dragging_tray and _current_tray_item and is_instance_valid(tray_artifact):
		tray_artifact.position = get_viewport().get_mouse_position() + _drag_offset
		# Highlight nearest shelf row
		_highlight_nearest_row(tray_artifact.position.y + 60.0)


func _input(event: InputEvent) -> void:
	# ── Escape closes dossier ─────────────────────────────────
	if event is InputEventKey and event.pressed and not event.echo:
		if event.keycode == KEY_ESCAPE and dossier_panel and dossier_panel.visible:
			_close_dossier()
			return

	# ── Mouse button ─────────────────────────────────────────
	if event is InputEventMouseButton:
		var mp: Vector2 = get_viewport().get_mouse_position()

		if event.button_index == MOUSE_BUTTON_LEFT:
			if event.pressed:
				# Start drag if cursor is over the artifact
				if _current_tray_item and is_instance_valid(tray_artifact) and tray_artifact.visible:
					if Rect2(tray_artifact.position, tray_artifact.size).has_point(mp):
						_drag_start_pos = mp
						_drag_offset    = tray_artifact.position - mp
						_dragging_tray  = true
						_set_row_overlays_visible(true)
						get_viewport().set_input_as_handled()
						return
			else:
				if _dragging_tray:
					_dragging_tray = false
					_set_row_overlays_visible(false)
					_clear_row_highlights()
					var dist: float = mp.distance_to(_drag_start_pos)
					if dist < 10.0:
						# Short tap → open dossier
						_return_artifact_to_tray()
						_show_tray_dossier()
					else:
						# Drop → use x to pick which shelf, y to pick which row
						var center_x: float = tray_artifact.position.x + 40.0
						var center_y: float = tray_artifact.position.y + 60.0
						var row := _get_row_from_y(center_y)
						if row >= 0:
							_try_place_in_row(row, center_x < 640.0)
						else:
							_return_artifact_to_tray()
					get_viewport().set_input_as_handled()
					return

		if not _dragging_tray:
			match event.button_index:
				MOUSE_BUTTON_WHEEL_DOWN:
					if event.pressed: _scroll( 120.0)
				MOUSE_BUTTON_WHEEL_UP:
					if event.pressed: _scroll(-120.0)

	elif event is InputEventMouseMotion:
		if event.button_mask & MOUSE_BUTTON_MASK_RIGHT:
			_scroll(-event.relative.x * 1.2)


func _scroll(px: float) -> void:
	var max_scroll := maxf(0.0, _max_col() * ITEM_SPACING)
	scroll_offset = clampf(scroll_offset + px, 0.0, max_scroll + 200.0)
	_reposition_all_items()
	if _bg_node:
		_bg_node.scroll_offset = scroll_offset
		_bg_node.queue_redraw()

func _max_col() -> int:
	var m := 0
	for id in item_layout:
		m = maxi(m, item_layout[id].col)
	return m


# ── SIGNALS ──────────────────────────────────────────────────────
func _connect_signals() -> void:
	ai_overseer.phase_changed.connect(_on_phase_changed)
	ai_overseer.knowledge_fed.connect(func(_a, _b): _refresh_ai_status())
	ai_overseer.suspicion_raised.connect(func(_a): _refresh_ai_status())
	ai_overseer.voice_line_spoken.connect(_on_ai_voice_spoken)
	research_manager.item_registered.connect(_on_item_registered)
	research_manager.research_advanced.connect(_on_research_advanced)
	research_manager.item_fully_researched.connect(_on_item_fully_researched)
	research_manager.series_completed.connect(_on_series_completed)

func _on_phase_changed(new_phase: String) -> void:
	_refresh_ai_status()
	var c := C_RED if new_phase == "Hostile" else C_AMBER
	_notify("// AI PHASE SHIFT → %s //" % new_phase.to_upper(), c)

func _on_ai_voice_spoken(text: String, phase: String) -> void:
	_notify("◆  " + text, _ai_voice_color(phase))

func _ai_voice_color(phase: String) -> Color:
	match phase:
		"Observing":   return Color(0.72, 0.84, 0.96)   # cold clinical blue-white
		"Assisting":   return Color(0.70, 0.95, 0.78)   # soft green-white
		"Controlling": return Color(0.95, 0.90, 0.55)   # warm amber-gold
		"Hostile":     return Color(1.00, 1.00, 1.00)   # stark white
	return C_TEXT

func _on_item_registered(item: ItemData)       -> void: _spawn_item_card(item)
func _on_research_advanced(item: ItemData)     -> void:
	if selected_item == item: _refresh_dossier()
func _on_item_fully_researched(item: ItemData) -> void:
	if selected_item == item: _refresh_dossier()
	_mark_card_complete(item)
	_notify("◈  RESEARCH COMPLETE — FEEDING AI: " + item.title.to_upper(), C_GREEN)
	if item.is_ai_sensitive:
		ai_overseer.speak("sensitive_filed")
func _on_series_completed(series_name: String, _items: Array) -> void:
	_notify("⚠  SERIES COMPLETE: " + series_name.to_upper() + " — MAJOR AI FEED EVENT", C_RED)
	ai_overseer.speak("series_complete")


# ── QUEUE & DELIVERY ─────────────────────────────────────────────
func _populate_queue() -> void:
	var dir := DirAccess.open("res://data/items/")
	if dir:
		dir.list_dir_begin()
		var f := dir.get_next()
		while f != "":
			if f.ends_with(".tres"):
				var item := load("res://data/items/" + f) as ItemData
				if item:
					if not ROW_CATEGORIES.has(item.category):
						item.category = "Intelligence"
					_incoming_queue.append(item)
			f = dir.get_next()
		dir.list_dir_end()

	var titles := [
		"MJ-12 Briefing Document", "Alien Autopsy Report", "UFO Recovery Log",
		"Mind Control Protocol", "Anti-Gravity Blueprint", "Tesla Archive",
		"Field Manual Black Ops", "Time Dilation Notes", "Psionics Research File",
		"Deep State Memorandum", "Shadow Government Charter", "The Majestic Files",
		"Dulce Base Schematics", "Montauk Project Summary", "Philadelphia Experiment Log",
		"Chemtrail Composition File", "HAARP Experiment Data", "Underground Facility Map",
		"Grey Alien Anatomy Report", "Reptilian Contact Dossier", "Nordic Encounter Report",
		"Formation Algorithm", "Black Budget Ledger", "MKULTRA Field Notes",
		"Perimeter Security Log", "Hangar 18 Manifest", "Drive Core Schematics",
		"Classified Satellite Photos", "Acoustic Device Specifications", "Subterranean Network Map"
	]
	var cls_list  := ["Top Secret", "Classified", "Confidential"]
	var org_list  := ["Nevada", "Roswell", "Washington DC", "Dulce", "Pentagon", "Langley", "Groom Lake"]
	var lore_list := [
		"Eyes only. Do not reproduce or distribute.",
		"Recovered from secondary site. Analysis ongoing.",
		"Provenance unverified. Handle with extreme caution.",
		"Transferred from Black Site 7. Q-clearance required.",
		"Classified by Executive Order 13526. Restricted.",
	]
	for i in range(20):
		var item               := ItemData.new()
		item.id                = "gen_%03d" % i
		var base_title: String  = titles[i % titles.size()]
		var vol                := (i / titles.size()) + 1
		item.title             = base_title if vol == 1 else ("%s Vol.%d" % [base_title, vol])
		item.classification    = cls_list[i % cls_list.size()]
		item.category          = ROW_CATEGORIES[i % ROW_CATEGORIES.size()]
		item.origin            = org_list[i % org_list.size()]
		item.ai_threat_level   = (i % 3) + 1
		item.containment_level = (i % 4) + 1
		item.research_stages   = (i % 3) + 2
		item.ai_feed_value     = 0.5 + (i % 5) * 0.3
		item.is_ai_sensitive   = (i % 7 == 0)
		item.lore_blurb        = lore_list[i % lore_list.size()]
		_incoming_queue.append(item)


func _deliver_next_item() -> void:
	if _artifacts_this_shift >= ARTIFACTS_PER_SHIFT:
		_end_shift()
		return
	if _incoming_queue.is_empty():
		_current_tray_item = null
		_update_tray_artifact()
		_notify("◈  ALL ARTIFACTS CATALOGUED", C_GREEN)
		_shift_active = false
		return
	_current_tray_item = _incoming_queue[0]
	_incoming_queue.remove_at(0)
	_artifacts_this_shift += 1
	_update_tray_artifact()
	_notify("▶  ARTIFACT INCOMING — CLICK TO INSPECT  ·  DRAG TO SHELF", C_AMBER)

func _end_shift() -> void:
	_shift_active = false
	_shift_timer  = 0.0
	_current_tray_item = null
	_update_tray_artifact()
	_close_dossier()
	_show_debrief()

func _start_next_shift() -> void:
	_current_shift       += 1
	_artifacts_this_shift = 0
	_shift_timer          = SHIFT_DURATION
	_shift_active         = true
	_refresh_shift_hud()
	_notify("▶  SHIFT %d — WATCH BEGINS" % _current_shift, C_AMBER)
	_deliver_next_item()

func _refresh_shift_hud() -> void:
	if not is_instance_valid(shift_label) or not is_instance_valid(timer_label):
		return
	shift_label.text = "SHIFT  %d / %d" % [_current_shift, SHIFT_COUNT]
	var secs := int(max(_shift_timer, 0.0))
	timer_label.text = "%d:%02d" % [secs / 60, secs % 60]
	timer_label.add_theme_color_override("font_color",
		C_RED if _shift_timer < 60.0 else C_DIM)


func _get_row_from_y(y: float) -> int:
	if y < 80.0 or y > 560.0:
		return -1
	var best_row := 0
	var best_dist: float = abs(y - SHELF_ROW_Y[0])
	for i in range(1, SHELF_ROWS):
		var d: float = abs(y - SHELF_ROW_Y[i])
		if d < best_dist:
			best_dist = d
			best_row  = i
	return best_row


func _try_place_in_row(row: int, on_left: bool = true) -> void:
	if not _current_tray_item: return
	var item := _current_tray_item
	var correct_row: int = ROW_CATEGORIES.find(item.category)
	if correct_row < 0:
		correct_row = 0

	if selected_item == item:
		_close_dossier()

	var is_correct := (row == correct_row)
	if not is_correct:
		_wrong_filed[item.id] = true   # must be set before _place_item_on_shelf spawns the card
		ai_overseer._raise_suspicion(8.0)
		ai_overseer.speak("mis_file")

	_place_item_on_shelf(item, row, on_left)
	_current_tray_item = null
	if is_instance_valid(tray_artifact):
		tray_artifact.visible = false
	if is_instance_valid(tray_hint_lbl):
		tray_hint_lbl.visible = false

	if is_correct:
		_notify("✓  FILED  —  %s" % ROW_CATEGORIES[row].to_upper(), C_GREEN)
	else:
		_notify("⚠  MIS-FILED  ·  CORRECT ROW: %s" % ROW_CATEGORIES[correct_row].to_upper(), C_RED)

	_deliver_next_item()


func _place_item_on_shelf(item: ItemData, row: int, on_left: bool = true) -> void:
	var col: int
	if on_left:
		col = _row_left_counts[row]
		_row_left_counts[row] += 1
	else:
		col = _row_right_counts[row]
		_row_right_counts[row] += 1
	item_layout[item.id] = {on_left = on_left, row = row, col = col}
	research_manager.register_item(item)


func _pickup_shelf_item(item: ItemData, card: Control) -> void:
	# Can't pick up while already holding something from the tray
	if _current_tray_item != null: return

	var layout = item_layout.get(item.id)
	if not layout: return

	var picked_row:  int  = layout.row
	var picked_col:  int  = layout.col
	var picked_left: bool = layout.on_left

	# Remove from layout and node maps
	item_layout.erase(item.id)
	item_nodes.erase(item.id)
	node_to_item.erase(card)

	# Shift all other items in same row/side with col > picked_col left by 1
	for other_id: String in item_layout:
		var ol = item_layout[other_id]
		if ol.row == picked_row and ol.on_left == picked_left and ol.col > picked_col:
			item_layout[other_id] = {on_left = ol.on_left, row = ol.row, col = ol.col - 1}
			_reposition_card(other_id)

	if picked_left:
		_row_left_counts[picked_row]  = maxi(0, _row_left_counts[picked_row]  - 1)
	else:
		_row_right_counts[picked_row] = maxi(0, _row_right_counts[picked_row] - 1)

	# Deselect if this was selected
	if selected_node == card:
		_set_card_selected(card, false)
		selected_node = null
		selected_item = null
		_close_dossier()

	card.queue_free()

	# Put item back in the tray so the player can re-place it
	_wrong_filed.erase(item.id)
	_current_tray_item = item
	_update_tray_artifact()
	if is_instance_valid(tray_hint_lbl):
		tray_hint_lbl.visible = true


func _return_artifact_to_tray() -> void:
	if not is_instance_valid(tray_artifact): return
	var tw := create_tween()
	tw.set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_BACK)
	tw.tween_property(tray_artifact, "position", ARTIFACT_HOME, 0.25)


func _flash_artifact_red() -> void:
	if not is_instance_valid(tray_artifact): return
	var s := tray_artifact.get_theme_stylebox("panel") as StyleBoxFlat
	if not s: return
	var prev := s.border_color
	s.border_color = C_RED
	s.set_border_width_all(4)
	var t := get_tree().create_timer(0.4)
	t.timeout.connect(func():
		if is_instance_valid(tray_artifact) and s:
			s.border_color = C_AMBER
			s.set_border_width_all(2)
	)


func _show_tray_dossier() -> void:
	if not _current_tray_item: return
	selected_item = _current_tray_item
	selected_node = null
	dossier_panel.visible = true
	_refresh_dossier()


# ── ROW OVERLAYS (shelf drop targets shown while dragging) ────────
const ROW_COLORS: Array[Color] = [
	Color(0.35, 0.55, 1.00),
	Color(0.15, 0.80, 0.45),
	Color(1.00, 0.60, 0.10),
	Color(0.70, 0.30, 0.90),
	Color(0.90, 0.22, 0.22),
]

func _build_row_overlays() -> void:
	var layer := CanvasLayer.new()
	layer.layer = 7
	add_child(layer)
	var root := Control.new()
	root.set_anchors_preset(Control.PRESET_FULL_RECT)
	root.mouse_filter = Control.MOUSE_FILTER_IGNORE
	layer.add_child(root)

	_row_overlays.clear()
	for i in range(SHELF_ROWS):
		var overlay := ColorRect.new()
		var ry: float = SHELF_ROW_Y[i]
		overlay.position = Vector2(0, ry - ITEM_H * 0.6)
		overlay.size     = Vector2(1280, ITEM_H * 1.2)
		overlay.color    = Color(ROW_COLORS[i].r, ROW_COLORS[i].g, ROW_COLORS[i].b, 0.0)
		overlay.mouse_filter = Control.MOUSE_FILTER_IGNORE
		overlay.visible  = false
		root.add_child(overlay)
		_row_overlays.append(overlay)

	# "FILE HERE" banner — appears at the correct row while dragging
	_filing_hint_overlay = Label.new()
	_filing_hint_overlay.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_filing_hint_overlay.add_theme_font_size_override("font_size", 14)
	_filing_hint_overlay.add_theme_color_override("font_color", C_GREEN)
	_filing_hint_overlay.size         = Vector2(1280, 24)
	_filing_hint_overlay.position     = Vector2(0, 200.0)
	_filing_hint_overlay.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_filing_hint_overlay.visible      = false
	root.add_child(_filing_hint_overlay)


func _set_row_overlays_visible(on: bool) -> void:
	var correct := -1
	if on and _current_tray_item:
		correct = ROW_CATEGORIES.find(_current_tray_item.category)
	for i in range(_row_overlays.size()):
		if not is_instance_valid(_row_overlays[i]): continue
		_row_overlays[i].visible = on
		var c: Color = ROW_COLORS[i]
		_row_overlays[i].color = Color(c.r, c.g, c.b, 0.35 if i == correct else 0.04)
	if is_instance_valid(_filing_hint_overlay):
		_filing_hint_overlay.visible = on
		if on and correct >= 0 and _current_tray_item:
			var c: Color = ROW_COLORS[correct]
			_filing_hint_overlay.position.y = SHELF_ROW_Y[correct] - 12.0
			_filing_hint_overlay.add_theme_color_override("font_color", c)
			_filing_hint_overlay.text = "▼   FILE HERE  —  %s  —  ▼" % ROW_CATEGORIES[correct].to_upper()


func _highlight_nearest_row(y: float) -> void:
	var correct := -1
	if _current_tray_item:
		correct = ROW_CATEGORIES.find(_current_tray_item.category)
	for i in range(_row_overlays.size()):
		if not is_instance_valid(_row_overlays[i]): continue
		var row_y: float = SHELF_ROW_Y[i]
		var dist: float  = abs(y - row_y)
		var c: Color = ROW_COLORS[i]
		if i == correct:
			# Correct row: always bright, extra glow when cursor is near it
			_row_overlays[i].color = Color(c.r, c.g, c.b, 0.55 if dist < ITEM_H * 1.5 else 0.32)
		else:
			_row_overlays[i].color = Color(c.r, c.g, c.b, 0.18 if dist < ITEM_H * 1.5 else 0.04)


func _clear_row_highlights() -> void:
	var correct := -1
	if _current_tray_item:
		correct = ROW_CATEGORIES.find(_current_tray_item.category)
	for i in range(_row_overlays.size()):
		if not is_instance_valid(_row_overlays[i]): continue
		var c: Color = ROW_COLORS[i]
		_row_overlays[i].color = Color(c.r, c.g, c.b, 0.32 if i == correct else 0.04)


# ── BACKGROUND ───────────────────────────────────────────────────
func _build_background() -> void:
	var layer := CanvasLayer.new()
	layer.layer = -1
	add_child(layer)
	var bg := Node2D.new()
	bg.set_script(load("res://scripts/warehouse_bg.gd"))
	layer.add_child(bg)
	_bg_node = bg


# ── ITEM LAYER ───────────────────────────────────────────────────
func _build_item_layer() -> void:
	item_layer = CanvasLayer.new()
	item_layer.layer = 5
	add_child(item_layer)
	_item_root = Control.new()
	_item_root.set_anchors_preset(Control.PRESET_FULL_RECT)
	_item_root.mouse_filter = Control.MOUSE_FILTER_PASS
	item_layer.add_child(_item_root)


# ── ITEM CARDS ───────────────────────────────────────────────────
# Visual type derived from title keywords
const _BLUEPRINT_WORDS := ["blueprint", "schematics", "diagram", "specifications", "map", "layout"]
const _FOLDER_WORDS    := ["log", "report", "briefing", "manual", "dossier", "notes", "memo", "file"]

func _item_is_blueprint(item: ItemData) -> bool:
	var t := item.title.to_lower()
	for w: String in _BLUEPRINT_WORDS:
		if t.contains(w): return true
	return false

func _item_is_folder(item: ItemData) -> bool:
	var t := item.title.to_lower()
	for w: String in _FOLDER_WORDS:
		if t.contains(w): return true
	return false

func _spawn_item_card(item: ItemData) -> void:
	if not item_layout.has(item.id):
		push_error("No layout for item: " + item.id)
		return

	var wrong := _wrong_filed.has(item.id)
	var correct_row: int = ROW_CATEGORIES.find(item.category)
	if correct_row < 0: correct_row = 0
	var row_c: Color = ROW_COLORS[correct_row]

	# ── Panel: transparent bg, StyleBoxFlat kept for hover/select border ──
	var card := Panel.new()
	card.size          = Vector2(ITEM_W, ITEM_H)
	card.clip_contents = true

	var cs := StyleBoxFlat.new()
	cs.bg_color     = Color(0, 0, 0, 0)
	cs.border_color = Color(0.82, 0.66, 0.24, 0.0)
	cs.set_border_width_all(0)
	cs.shadow_color  = Color(0, 0, 0, 0.88)
	cs.shadow_size   = 7
	cs.shadow_offset = Vector2(3, 3)
	card.add_theme_stylebox_override("panel", cs)

	# ── Layer 1: SVG book-cover texture ──────────────────────────
	var tex_rect := TextureRect.new()
	tex_rect.position     = Vector2.ZERO
	tex_rect.size         = Vector2(ITEM_W, ITEM_H)
	tex_rect.expand_mode  = TextureRect.EXPAND_IGNORE_SIZE
	tex_rect.stretch_mode = TextureRect.STRETCH_SCALE
	tex_rect.mouse_filter = Control.MOUSE_FILTER_PASS
	var img := Image.new()
	img.load_svg_from_buffer(_build_crate_svg(item, wrong), 4.0)
	tex_rect.texture = ImageTexture.create_from_image(img)
	card.add_child(tex_rect)

	# ── Layer 2: Text overlay (gold title + classification abbrev) ─
	var overlay := Control.new()
	overlay.position     = Vector2.ZERO
	overlay.size         = Vector2(ITEM_W, ITEM_H)
	overlay.mouse_filter = Control.MOUSE_FILTER_PASS
	card.add_child(overlay)

	var is_bp  := _item_is_blueprint(item)
	# Text on crate: white stencil on classification band
	var txt_col := Color(0.92, 0.90, 0.82) if not wrong else Color(0.80, 0.52, 0.46)

	var title_lbl := Label.new()
	title_lbl.text                 = _truncate(item.title, 14)
	title_lbl.set_position(Vector2(5, 14))
	title_lbl.set_size(Vector2(24, 22))
	title_lbl.autowrap_mode        = TextServer.AUTOWRAP_WORD_SMART
	title_lbl.vertical_alignment   = VERTICAL_ALIGNMENT_CENTER
	title_lbl.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	title_lbl.mouse_filter         = Control.MOUSE_FILTER_PASS
	title_lbl.add_theme_font_size_override("font_size", 5)
	title_lbl.add_theme_color_override("font_color", txt_col)
	overlay.add_child(title_lbl)

	var cls_lbl := Label.new()
	cls_lbl.text                 = item.classification.left(2).to_upper()
	cls_lbl.set_position(Vector2(5, 38))
	cls_lbl.set_size(Vector2(24, 8))
	cls_lbl.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	cls_lbl.mouse_filter         = Control.MOUSE_FILTER_PASS
	cls_lbl.add_theme_font_size_override("font_size", 5)
	cls_lbl.add_theme_color_override("font_color", Color(0.70, 0.68, 0.58, 0.85))
	overlay.add_child(cls_lbl)

	card.mouse_filter = Control.MOUSE_FILTER_STOP
	card.gui_input.connect(func(ev: InputEvent):
		if ev is InputEventMouseButton and ev.pressed:
			match ev.button_index:
				MOUSE_BUTTON_LEFT:       _select_item(item, card)
				MOUSE_BUTTON_RIGHT:      _pickup_shelf_item(item, card)
				MOUSE_BUTTON_WHEEL_DOWN: _scroll( 120.0)
				MOUSE_BUTTON_WHEEL_UP:   _scroll(-120.0)
	)
	card.mouse_entered.connect(func(): _set_card_hover(card, true))
	card.mouse_exited.connect(func():  _set_card_hover(card, false))

	_item_root.add_child(card)
	item_nodes[item.id] = card
	node_to_item[card]  = item
	_reposition_card(item.id)


func _reposition_all_items() -> void:
	for id in item_nodes:
		_reposition_card(id)

func _reposition_card(item_id: String) -> void:
	var card   = item_nodes.get(item_id)
	var layout = item_layout.get(item_id)
	if not card or not layout: return
	var base_x: float = LEFT_SHELF_X if layout.on_left else RIGHT_SHELF_X
	var x: float      = base_x + layout.col * ITEM_SPACING - scroll_offset - ITEM_W * 0.5
	var y: float      = SHELF_ROW_Y[layout.row] - ITEM_H * 0.5
	card.position = Vector2(x, y)


# ── ITEM SELECTION ───────────────────────────────────────────────
func _select_item(item: ItemData, card: Control) -> void:
	if selected_node:
		_set_card_selected(selected_node, false)
	selected_item = item
	selected_node = card
	_set_card_selected(card, true)
	dossier_panel.visible = true
	_refresh_dossier()
	if item.ai_threat_level >= 3:
		ai_overseer.speak("threat_opened")

func _set_card_selected(card: Control, on: bool) -> void:
	var s := card.get_theme_stylebox("panel") as StyleBoxFlat
	if not s: return
	if on:
		s.border_color = Color(0.95, 0.75, 0.25)   # bright gold
		s.set_border_width_all(2)
		s.shadow_color = Color(0.85, 0.65, 0.15, 0.80)
		s.shadow_size  = 8
	else:
		s.set_border_width_all(0)
		s.shadow_color = Color(0, 0, 0, 0.90)
		s.shadow_size  = 7

func _set_card_hover(card: Control, on: bool) -> void:
	if card == selected_node: return
	var s := card.get_theme_stylebox("panel") as StyleBoxFlat
	if not s: return
	if on:
		s.border_color = Color(0.80, 0.65, 0.28, 0.75)   # soft gold glow
		s.set_border_width_all(1)
		s.shadow_color = Color(0.70, 0.55, 0.20, 0.55)
		s.shadow_size  = 5
	else:
		s.set_border_width_all(0)
		s.shadow_color = Color(0, 0, 0, 0.90)
		s.shadow_size  = 7

func _mark_card_complete(item: ItemData) -> void:
	var card = item_nodes.get(item.id)
	if not card: return
	var s := card.get_theme_stylebox("panel") as StyleBoxFlat
	if s:
		s.border_color = C_GREEN
		s.set_border_width_all(2)

func _close_dossier() -> void:
	dossier_panel.visible = false
	if selected_node:
		_set_card_selected(selected_node, false)
	selected_item = null
	selected_node = null

func _on_research_btn_pressed() -> void:
	if selected_item and not selected_item.is_fully_researched():
		research_manager.advance_research(selected_item.id, 1)


# ── HELPERS ──────────────────────────────────────────────────────
func _cls_color(cls: String) -> Color:
	match cls:
		"Top Secret":   return Color(0.55, 0.07, 0.07)   # blood crimson
		"Classified":   return Color(0.08, 0.12, 0.58)   # deep royal blue
		"Confidential": return Color(0.08, 0.42, 0.12)   # forest emerald
		_:              return Color(0.34, 0.09, 0.50)   # arcane violet

func _threat_color(level: int) -> Color:
	if level >= 3: return C_RED
	if level >= 2: return C_AMBER
	return C_GREEN

func _truncate(s: String, n: int) -> String:
	return s if s.length() <= n else s.left(n - 1) + "…"


# ── AI STATUS ────────────────────────────────────────────────────
func _refresh_ai_status() -> void:
	phase_label.text     = "[ %s ]" % ai_overseer.current_phase.to_upper()
	phase_label.add_theme_color_override("font_color", _phase_color(ai_overseer.current_phase))
	knowledge_label.text = "KNOWLEDGE  %.1f" % ai_overseer.total_knowledge
	suspicion_label.text = "SUSPICION  %.0f%%" % ai_overseer.suspicion_level

func _phase_color(p: String) -> Color:
	match p:
		"Dormant":     return C_DIM
		"Observing":   return C_GREEN
		"Assisting":   return C_AMBER
		"Controlling": return C_AMBER
		"Hostile":     return C_RED
	return C_TEXT

func _notify(msg: String, color: Color = C_AMBER) -> void:
	notification_label.text = msg
	notification_label.add_theme_color_override("font_color", color)
	notification_label.visible = true
	notification_timer = 4.5


# ── TRAY ARTIFACT ────────────────────────────────────────────────
func _update_tray_artifact() -> void:
	if not is_instance_valid(tray_artifact): return

	if _current_tray_item:
		var item := _current_tray_item
		tray_artifact.position = ARTIFACT_HOME
		tray_artifact.visible  = true
		if is_instance_valid(tray_hint_lbl):
			tray_hint_lbl.visible = true
		if is_instance_valid(tray_next_lbl):
			tray_next_lbl.visible = false

		# Regenerate SVG background
		if is_instance_valid(_artifact_tex_rect):
			var img := Image.new()
			img.load_svg_from_buffer(_build_artifact_svg(item), 2.0)
			_artifact_tex_rect.texture = ImageTexture.create_from_image(img)
		# Update text labels
		var threat := item.ai_threat_level
		var banner_text := "ROUTINE INTAKE"
		if threat >= 3:   banner_text = "TOP  SECRET"
		elif threat >= 2: banner_text = "SENSITIVE"
		if is_instance_valid(_art_banner_lbl):
			_art_banner_lbl.text = banner_text
		if is_instance_valid(_art_title_lbl):
			_art_title_lbl.text = item.title
		if is_instance_valid(_art_cat_lbl):
			_art_cat_lbl.text = item.category.to_upper()
			var cat_row := ROW_CATEGORIES.find(item.category)
			if cat_row >= 0:
				_art_cat_lbl.add_theme_color_override("font_color", ROW_COLORS[cat_row])
		if is_instance_valid(tray_hint_lbl):
			tray_hint_lbl.text = "CLICK TO READ  ·  DRAG TO %s ROW  ▶" % item.category.to_upper()

	else:
		tray_artifact.visible = false
		if is_instance_valid(tray_hint_lbl):
			tray_hint_lbl.visible = false


# ═══════════════════════════════════════════════════════════════
#  UI BUILDING
# ═══════════════════════════════════════════════════════════════
func _build_ui() -> void:
	_build_hud()
	_build_tray_artifact_ui()
	_build_dossier_panel()
	_build_debrief_ui()

func _mk_style(bg: Color = C_PANEL, border: Color = C_BORDER, margin: float = 14.0) -> StyleBoxFlat:
	var s := StyleBoxFlat.new()
	s.bg_color     = bg
	s.border_color = border
	s.set_border_width_all(1)
	s.content_margin_left   = margin
	s.content_margin_top    = margin
	s.content_margin_right  = margin
	s.content_margin_bottom = margin
	return s

func _mk_label(txt: String, size: int = 16, color: Color = C_TEXT) -> Label:
	var l := Label.new()
	l.text = txt
	l.add_theme_font_size_override("font_size", size)
	l.add_theme_color_override("font_color", color)
	return l

func _build_hud() -> void:
	var hud := CanvasLayer.new()
	hud.layer = 10
	add_child(hud)

	var root := Control.new()
	root.set_anchors_preset(Control.PRESET_FULL_RECT)
	root.mouse_filter = Control.MOUSE_FILTER_IGNORE
	hud.add_child(root)

	# Top bar
	var bar := PanelContainer.new()
	bar.set_anchors_preset(Control.PRESET_TOP_WIDE)
	bar.custom_minimum_size = Vector2(0, 58)
	bar.add_theme_stylebox_override("panel",
		_mk_style(Color(C_PANEL.r, C_PANEL.g, C_PANEL.b, 0.92), C_BORDER, 14.0))
	root.add_child(bar)

	var hbox := HBoxContainer.new()
	hbox.add_theme_constant_override("separation", 28)
	bar.add_child(hbox)

	var title_lbl := _mk_label("◈  WAREHOUSE 51  //  ARTIFACT CATALOGUING DIVISION  //  CLASSIFIED", 17, C_HEADER)
	title_lbl.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	title_lbl.vertical_alignment    = VERTICAL_ALIGNMENT_CENTER
	hbox.add_child(title_lbl)

	var cluster := HBoxContainer.new()
	cluster.add_theme_constant_override("separation", 20)
	hbox.add_child(cluster)

	phase_label = _mk_label("[ DORMANT ]", 15, C_DIM)
	phase_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	cluster.add_child(phase_label)

	knowledge_label = _mk_label("KNOWLEDGE  0.0", 14, C_DIM)
	knowledge_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	cluster.add_child(knowledge_label)

	suspicion_label = _mk_label("SUSPICION  0%", 14, C_DIM)
	suspicion_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	cluster.add_child(suspicion_label)

	shift_label = _mk_label("SHIFT  1 / %d" % SHIFT_COUNT, 14, C_DIM)
	shift_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	cluster.add_child(shift_label)

	timer_label = _mk_label("7:00", 14, C_DIM)
	timer_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	cluster.add_child(timer_label)

	# Notification strip
	var notif_panel := PanelContainer.new()
	notif_panel.set_anchors_preset(Control.PRESET_BOTTOM_WIDE)
	notif_panel.custom_minimum_size = Vector2(0, 36)
	notif_panel.add_theme_stylebox_override("panel",
		_mk_style(Color(0.09, 0.06, 0.03, 0.90), C_AMBER, 12.0))
	root.add_child(notif_panel)

	notification_label = _mk_label("", 14, C_AMBER)
	notification_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	notification_label.vertical_alignment   = VERTICAL_ALIGNMENT_CENTER
	notification_label.visible = false
	notif_panel.add_child(notification_label)


func _build_tray_artifact_ui() -> void:
	var layer := CanvasLayer.new()
	layer.layer = 9
	add_child(layer)

	var root := Control.new()
	root.set_anchors_preset(Control.PRESET_FULL_RECT)
	root.mouse_filter = Control.MOUSE_FILTER_IGNORE
	layer.add_child(root)

	# Shelf row labels — coloured accent bar + category name
	var row_colors: Array[Color] = [
		Color(0.35, 0.55, 1.00),   # Intelligence   – blue
		Color(0.15, 0.80, 0.45),   # Research        – green
		Color(1.00, 0.60, 0.10),   # Field Report    – amber
		Color(0.70, 0.30, 0.90),   # Technical       – purple
		Color(0.90, 0.22, 0.22),   # Biological      – red
	]
	for i in range(SHELF_ROWS):
		var row_c: Color = row_colors[i]
		# Coloured vertical accent bar
		var bar := ColorRect.new()
		bar.color    = row_c
		bar.position = Vector2(0, SHELF_ROW_Y[i] - ITEM_H * 0.5)
		bar.size     = Vector2(3, ITEM_H)
		bar.mouse_filter = Control.MOUSE_FILTER_IGNORE
		root.add_child(bar)
		# Category text
		var lbl := _mk_label(ROW_CATEGORIES[i].to_upper(), 11, row_c)
		lbl.position     = Vector2(6, SHELF_ROW_Y[i] - 9)
		lbl.mouse_filter = Control.MOUSE_FILTER_IGNORE
		root.add_child(lbl)

	# "Next artifact" countdown label (lower right, above artifact home)
	tray_next_lbl = _mk_label("", 13, C_DIM)
	tray_next_lbl.position = Vector2(940, 543)
	tray_next_lbl.mouse_filter = Control.MOUSE_FILTER_IGNORE
	tray_next_lbl.visible = false
	root.add_child(tray_next_lbl)

	# Hint label above the artifact — updated dynamically with the target row
	tray_hint_lbl = _mk_label("", 10, C_DIM)
	tray_hint_lbl.position = Vector2(870, 550)
	tray_hint_lbl.mouse_filter = Control.MOUSE_FILTER_IGNORE
	tray_hint_lbl.visible = false
	root.add_child(tray_hint_lbl)

	# ── The artifact card itself ──────────────────────────────
	# PanelContainer with two stacked children:
	#   child 1 → TextureRect (SVG background)
	#   child 2 → Control overlay (Labels on top)
	# PanelContainer sizes both children to its content area, so they overlap.
	tray_artifact = PanelContainer.new()
	tray_artifact.custom_minimum_size = Vector2(140, 160)
	tray_artifact.size                = Vector2(140, 160)
	tray_artifact.position            = ARTIFACT_HOME
	tray_artifact.add_theme_stylebox_override("panel", StyleBoxEmpty.new())
	tray_artifact.mouse_filter        = Control.MOUSE_FILTER_IGNORE
	tray_artifact.visible             = false
	root.add_child(tray_artifact)

	# Layer 1: SVG texture fills the card
	_artifact_tex_rect = TextureRect.new()
	_artifact_tex_rect.expand_mode  = TextureRect.EXPAND_IGNORE_SIZE
	_artifact_tex_rect.stretch_mode = TextureRect.STRETCH_SCALE
	_artifact_tex_rect.mouse_filter = Control.MOUSE_FILTER_IGNORE
	tray_artifact.add_child(_artifact_tex_rect)

	# Layer 2: text overlay — a plain Control that PanelContainer also sizes to fill
	var overlay := Control.new()
	overlay.mouse_filter = Control.MOUSE_FILTER_IGNORE
	tray_artifact.add_child(overlay)

	_art_banner_lbl = Label.new()
	_art_banner_lbl.set_position(Vector2(0, 4))
	_art_banner_lbl.set_size(Vector2(140, 18))
	_art_banner_lbl.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_art_banner_lbl.add_theme_font_size_override("font_size", 8)
	_art_banner_lbl.add_theme_color_override("font_color", Color(1, 1, 1))
	_art_banner_lbl.mouse_filter = Control.MOUSE_FILTER_IGNORE
	overlay.add_child(_art_banner_lbl)

	_art_title_lbl = Label.new()
	_art_title_lbl.set_position(Vector2(8, 54))
	_art_title_lbl.set_size(Vector2(124, 48))
	_art_title_lbl.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_art_title_lbl.vertical_alignment   = VERTICAL_ALIGNMENT_CENTER
	_art_title_lbl.autowrap_mode        = TextServer.AUTOWRAP_WORD_SMART
	_art_title_lbl.add_theme_font_size_override("font_size", 9)
	_art_title_lbl.add_theme_color_override("font_color", Color(0.16, 0.12, 0.04))
	_art_title_lbl.mouse_filter = Control.MOUSE_FILTER_IGNORE
	overlay.add_child(_art_title_lbl)

	_art_cat_lbl = Label.new()
	_art_cat_lbl.set_position(Vector2(10, 128))
	_art_cat_lbl.set_size(Vector2(120, 24))
	_art_cat_lbl.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_art_cat_lbl.vertical_alignment   = VERTICAL_ALIGNMENT_CENTER
	_art_cat_lbl.autowrap_mode        = TextServer.AUTOWRAP_OFF
	_art_cat_lbl.add_theme_font_size_override("font_size", 10)
	_art_cat_lbl.mouse_filter = Control.MOUSE_FILTER_IGNORE
	overlay.add_child(_art_cat_lbl)


func _build_crate_svg(item: ItemData, wrong: bool) -> PackedByteArray:
	# Wood tone
	var wb: Color  # base plank
	var wm: Color  # mid plank
	var wd: Color  # dark groove / frame
	if wrong:
		wb = Color(0.42, 0.22, 0.12)
		wm = Color(0.34, 0.17, 0.09)
		wd = Color(0.22, 0.11, 0.06)
	elif _item_is_blueprint(item):
		wb = Color(0.30, 0.34, 0.42)
		wm = Color(0.24, 0.27, 0.34)
		wd = Color(0.14, 0.16, 0.22)
	else:
		wb = Color(0.58, 0.44, 0.20)
		wm = Color(0.48, 0.36, 0.16)
		wd = Color(0.30, 0.22, 0.08)

	# Classification band colour
	var band: Color
	if wrong:
		band = Color(0.48, 0.08, 0.08)
	else:
		band = _cls_color(item.classification).darkened(0.25)

	# Threat indicator strip colour (top-left corner mark)
	var thr: Color
	match item.ai_threat_level:
		3: thr = Color(0.82, 0.08, 0.08)
		2: thr = Color(0.80, 0.52, 0.06)
		_: thr = Color(0.48, 0.50, 0.52)

	var shadow_top := Color(0, 0, 0, 0.22).to_html(true)
	var shadow_side := Color(0, 0, 0, 0.14).to_html(true)
	var svg := """<svg xmlns="http://www.w3.org/2000/svg" width="34" height="52">
<rect width="34" height="52" fill="#{wb}"/>
<rect y="11" width="34" height="1" fill="#{wd}"/>
<rect y="12" width="34" height="12" fill="#{wm}"/>
<rect y="24" width="34" height="1" fill="#{wd}"/>
<rect y="25" width="34" height="10" fill="#{wb}"/>
<rect y="35" width="34" height="1" fill="#{wd}"/>
<rect y="36" width="34" height="15" fill="#{wm}"/>
<rect y="51" width="34" height="1" fill="#{wd}"/>
<rect width="5" height="52" fill="#{wd}"/>
<rect x="2" width="2" height="52" fill="#{wm}"/>
<rect x="29" width="5" height="52" fill="#{wd}"/>
<rect x="30" width="2" height="52" fill="#{wm}"/>
<rect x="5" y="13" width="24" height="21" fill="#{band}"/>
<rect x="5" y="13" width="24" height="1" fill="#111111"/>
<rect x="5" y="33" width="24" height="1" fill="#111111"/>
<rect x="0" y="0" width="7" height="2" fill="#999999"/>
<rect x="0" y="0" width="2" height="7" fill="#999999"/>
<rect x="27" y="0" width="7" height="2" fill="#999999"/>
<rect x="32" y="0" width="2" height="7" fill="#999999"/>
<rect x="0" y="50" width="7" height="2" fill="#999999"/>
<rect x="0" y="45" width="2" height="7" fill="#999999"/>
<rect x="27" y="50" width="7" height="2" fill="#999999"/>
<rect x="32" y="45" width="2" height="7" fill="#999999"/>
<rect x="5" y="3" width="8" height="6" fill="#{thr}"/>
<rect width="34" height="3" fill="#{sh_top}"/>
<rect x="31" width="3" height="52" fill="#{sh_side}"/>
</svg>""".format({
		"wb":      wb.to_html(false),
		"wm":      wm.to_html(false),
		"wd":      wd.to_html(false),
		"band":    band.to_html(false),
		"thr":     thr.to_html(false),
		"sh_top":  shadow_top,
		"sh_side": shadow_side,
	})
	return svg.to_utf8_buffer()


func _build_shelf_item_svg(item: ItemData, wrong: bool) -> PackedByteArray:
	var tc := _threat_color(item.ai_threat_level)
	if wrong:
		tc = Color(0.55, 0.06, 0.06)

	var paper := Color(0.83, 0.77, 0.64)
	match item.classification:
		"Top Secret":   paper = Color(0.88, 0.77, 0.70)
		"Classified":   paper = Color(0.76, 0.80, 0.88)
		"Confidential": paper = Color(0.78, 0.86, 0.77)
	if wrong:
		paper = Color(0.58, 0.54, 0.50)

	var paper_dark := Color(maxf(paper.r - 0.07, 0.0), maxf(paper.g - 0.07, 0.0), maxf(paper.b - 0.07, 0.0))
	var cat_row    := ROW_CATEGORIES.find(item.category)
	var row_c      := ROW_COLORS[cat_row] if cat_row >= 0 else C_GREEN
	var cat_bg     := Color(row_c.r * 0.18, row_c.g * 0.18, row_c.b * 0.18) if not wrong else Color(0.20, 0.04, 0.04)

	var svg := """<svg xmlns="http://www.w3.org/2000/svg" width="34" height="52">
<rect width="34" height="52" rx="1" fill="#{paper}"/>
<rect x="0" y="0" width="34" height="8" rx="1" fill="#{tc}"/>
<rect x="0" y="4" width="34" height="4" fill="#{tc}"/>
<rect x="2" y="33" width="19" height="2" rx="0.5" fill="#140c06"/>
<rect x="2" y="37" width="13" height="2" rx="0.5" fill="#1e1408"/>
<line x1="2" y1="41" x2="32" y2="41" stroke="#{paper_dark}" stroke-width="0.5"/>
<rect x="0" y="42" width="34" height="10" fill="#{cat_bg}"/>
<polygon points="34,0 34,8 26,0" fill="#{tc_dark}"/>
</svg>""".format({
		"paper":      paper.to_html(false),
		"paper_dark": paper_dark.to_html(false),
		"tc":         tc.to_html(false),
		"tc_dark":    tc.darkened(0.4).to_html(false),
		"cat_bg":     cat_bg.to_html(false),
	})
	return svg.to_utf8_buffer()


func _build_artifact_svg(item: ItemData) -> PackedByteArray:
	var tc := _threat_color(item.ai_threat_level)

	var paper := Color(0.87, 0.81, 0.69)
	match item.classification:
		"Top Secret":   paper = Color(0.90, 0.80, 0.72)
		"Classified":   paper = Color(0.80, 0.83, 0.90)
		"Confidential": paper = Color(0.82, 0.89, 0.80)
	var paper_dark := Color(maxf(paper.r - 0.06, 0.0), maxf(paper.g - 0.06, 0.0), maxf(paper.b - 0.06, 0.0))

	var cat_row := ROW_CATEGORIES.find(item.category)
	var cat_color := ROW_COLORS[cat_row] if cat_row >= 0 else C_GREEN
	var cat_bg := Color(cat_color.r * 0.12, cat_color.g * 0.12, cat_color.b * 0.12)

	var svg := """<svg xmlns="http://www.w3.org/2000/svg" width="140" height="160">
<rect width="140" height="160" rx="3" fill="#{paper}"/>
<line x1="10" y1="52" x2="130" y2="52" stroke="#{paper_dark}" stroke-width="0.5"/>
<rect x="0" y="0" width="140" height="24" rx="3" fill="#{tc}"/>
<rect x="0" y="18" width="140" height="6" fill="#{tc}"/>
<rect x="10" y="106" width="55" height="5" rx="1" fill="#140c06"/>
<rect x="72" y="106" width="44" height="5" rx="1" fill="#140c06"/>
<rect x="10" y="114" width="80" height="5" rx="1" fill="#1e1408"/>
<line x1="10" y1="122" x2="130" y2="122" stroke="#{paper_dark}" stroke-width="0.5"/>
<rect x="10" y="128" width="120" height="24" rx="2" fill="#{cat_bg}"/>
<rect x="1" y="1" width="138" height="158" rx="3" fill="none" stroke="#907850" stroke-width="1.5"/>
<polygon points="140,0 140,18 122,0" fill="#{tc_dark}"/>
</svg>""".format({
		"paper":      paper.to_html(false),
		"paper_dark": paper_dark.to_html(false),
		"tc":         tc.to_html(false),
		"tc_dark":    tc.darkened(0.4).to_html(false),
		"cat_bg":     cat_bg.to_html(false),
	})

	return svg.to_utf8_buffer()


func _build_dossier_panel() -> void:
	var layer := CanvasLayer.new()
	layer.layer = 11
	add_child(layer)

	var root := Control.new()
	root.set_anchors_preset(Control.PRESET_FULL_RECT)
	root.mouse_filter = Control.MOUSE_FILTER_IGNORE
	layer.add_child(root)

	dossier_panel = PanelContainer.new()
	dossier_panel.anchor_left   = 0.63
	dossier_panel.anchor_top    = 0.0
	dossier_panel.anchor_right  = 1.0
	dossier_panel.anchor_bottom = 1.0
	dossier_panel.offset_top    = 58
	dossier_panel.add_theme_stylebox_override("panel",
		_mk_style(Color(C_PANEL.r, C_PANEL.g, C_PANEL.b, 0.96), C_BORDER, 18.0))
	dossier_panel.visible = false
	root.add_child(dossier_panel)

	var scroll := ScrollContainer.new()
	scroll.set_anchors_preset(Control.PRESET_FULL_RECT)
	scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	dossier_panel.add_child(scroll)

	var vbox := VBoxContainer.new()
	vbox.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	vbox.add_theme_constant_override("separation", 10)
	scroll.add_child(vbox)

	# Close button
	var close_row := HBoxContainer.new()
	close_row.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	var spacer_c := Control.new()
	spacer_c.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	close_row.add_child(spacer_c)
	var close_btn := Button.new()
	close_btn.text = "✕  CLOSE"
	close_btn.add_theme_font_size_override("font_size", 12)
	close_btn.add_theme_color_override("font_color", C_DIM)
	close_btn.add_theme_stylebox_override("normal",  StyleBoxEmpty.new())
	close_btn.add_theme_stylebox_override("hover",   StyleBoxEmpty.new())
	close_btn.add_theme_stylebox_override("pressed", StyleBoxEmpty.new())
	close_btn.add_theme_stylebox_override("focus",   StyleBoxEmpty.new())
	close_btn.mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	close_btn.pressed.connect(_close_dossier)
	close_row.add_child(close_btn)
	vbox.add_child(close_row)

	d_title = _mk_label("", 23, C_HEADER)
	d_title.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	vbox.add_child(d_title)

	d_classify = _mk_label("", 13, C_AMBER)
	d_classify.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	vbox.add_child(d_classify)

	_dsep(vbox)

	d_meta = _mk_label("", 14, C_DIM)
	d_meta.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	vbox.add_child(d_meta)

	_dsep(vbox)

	vbox.add_child(_mk_label("  FIELD NOTES", 13, C_DIM))

	d_lore = RichTextLabel.new()
	d_lore.bbcode_enabled      = false
	d_lore.custom_minimum_size = Vector2(0, 110)
	d_lore.size_flags_vertical = Control.SIZE_EXPAND_FILL
	d_lore.add_theme_color_override("default_color", C_TEXT)
	d_lore.add_theme_font_size_override("normal_font_size", 15)
	vbox.add_child(d_lore)

	_dsep(vbox)

	vbox.add_child(_mk_label("  RESEARCH PROGRESS", 13, C_DIM))

	d_research_bar = ProgressBar.new()
	d_research_bar.custom_minimum_size = Vector2(0, 22)
	d_research_bar.show_percentage     = false
	var bar_fill := StyleBoxFlat.new()
	bar_fill.bg_color = C_GREEN
	var bar_bg        := StyleBoxFlat.new()
	bar_bg.bg_color    = Color(0.04, 0.09, 0.06)
	bar_bg.border_color = C_BORDER
	bar_bg.set_border_width_all(1)
	d_research_bar.add_theme_stylebox_override("fill",       bar_fill)
	d_research_bar.add_theme_stylebox_override("background", bar_bg)
	vbox.add_child(d_research_bar)

	d_research_stage = _mk_label("", 13, C_DIM)
	vbox.add_child(d_research_stage)

	d_ai_sensitivity = _mk_label("", 13, C_DIM)
	d_ai_sensitivity.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	vbox.add_child(d_ai_sensitivity)

	d_research_btn = Button.new()
	d_research_btn.custom_minimum_size = Vector2(0, 50)
	d_research_btn.add_theme_font_size_override("font_size", 16)
	d_research_btn.pressed.connect(_on_research_btn_pressed)
	vbox.add_child(d_research_btn)

	# AI annotation — only visible when the AI has something to say about this item
	d_ai_annotation = _mk_label("", 13, Color(0.72, 0.84, 0.96))
	d_ai_annotation.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	d_ai_annotation.visible = false
	vbox.add_child(d_ai_annotation)

# ═══════════════════════════════════════════════════════════════
#  DEBRIEF SYSTEM
# ═══════════════════════════════════════════════════════════════

func _build_debrief_ui() -> void:
	var layer := CanvasLayer.new()
	layer.layer = 15
	add_child(layer)

	# Single root — hidden until debrief fires; overlay lives inside so it's hidden too
	debrief_root = Control.new()
	debrief_root.set_anchors_preset(Control.PRESET_FULL_RECT)
	debrief_root.mouse_filter = Control.MOUSE_FILTER_STOP
	debrief_root.visible = false
	layer.add_child(debrief_root)

	# Full-screen dark overlay
	var overlay := ColorRect.new()
	overlay.set_anchors_preset(Control.PRESET_FULL_RECT)
	overlay.color = Color(0.02, 0.03, 0.06, 0.92)
	overlay.mouse_filter = Control.MOUSE_FILTER_IGNORE
	debrief_root.add_child(overlay)

	# Centered panel — 52% width, 72% height
	debrief_panel = PanelContainer.new()
	debrief_panel.anchor_left   = 0.24
	debrief_panel.anchor_top    = 0.10
	debrief_panel.anchor_right  = 0.76
	debrief_panel.anchor_bottom = 0.90
	var ps := StyleBoxFlat.new()
	ps.bg_color     = Color(0.04, 0.05, 0.09, 0.98)
	ps.border_color = Color(0.28, 0.42, 0.62)
	ps.set_border_width_all(1)
	ps.content_margin_left   = 40.0
	ps.content_margin_top    = 36.0
	ps.content_margin_right  = 40.0
	ps.content_margin_bottom = 36.0
	debrief_panel.add_theme_stylebox_override("panel", ps)
	debrief_root.add_child(debrief_panel)

	var vbox := VBoxContainer.new()
	vbox.set_anchors_preset(Control.PRESET_FULL_RECT)
	vbox.add_theme_constant_override("separation", 18)
	debrief_panel.add_child(vbox)

	# Shift header
	_db_shift_lbl = Label.new()
	_db_shift_lbl.add_theme_font_size_override("font_size", 13)
	_db_shift_lbl.add_theme_color_override("font_color", Color(0.30, 0.45, 0.65))
	vbox.add_child(_db_shift_lbl)

	var sep1 := HSeparator.new()
	sep1.add_theme_color_override("color", Color(0.18, 0.26, 0.40))
	vbox.add_child(sep1)

	# AI preamble
	_db_preamble_lbl = Label.new()
	_db_preamble_lbl.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	_db_preamble_lbl.add_theme_font_size_override("font_size", 15)
	_db_preamble_lbl.add_theme_color_override("font_color", Color(0.72, 0.84, 0.96))
	vbox.add_child(_db_preamble_lbl)

	var sep2 := HSeparator.new()
	sep2.add_theme_color_override("color", Color(0.18, 0.26, 0.40))
	vbox.add_child(sep2)

	# Progress
	_db_progress_lbl = Label.new()
	_db_progress_lbl.add_theme_font_size_override("font_size", 11)
	_db_progress_lbl.add_theme_color_override("font_color", Color(0.28, 0.40, 0.55))
	vbox.add_child(_db_progress_lbl)

	# Question
	_db_question_lbl = Label.new()
	_db_question_lbl.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	_db_question_lbl.add_theme_font_size_override("font_size", 20)
	_db_question_lbl.add_theme_color_override("font_color", Color(0.90, 0.92, 0.96))
	_db_question_lbl.size_flags_vertical = Control.SIZE_EXPAND_FILL
	vbox.add_child(_db_question_lbl)

	# Answer buttons container
	_db_answers_box = VBoxContainer.new()
	_db_answers_box.add_theme_constant_override("separation", 10)
	vbox.add_child(_db_answers_box)

	# AI response line (shown briefly after an answer)
	_db_response_lbl = Label.new()
	_db_response_lbl.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	_db_response_lbl.add_theme_font_size_override("font_size", 16)
	_db_response_lbl.add_theme_color_override("font_color", Color(0.72, 0.84, 0.96))
	_db_response_lbl.visible = false
	vbox.add_child(_db_response_lbl)

	var sep3 := HSeparator.new()
	sep3.add_theme_color_override("color", Color(0.18, 0.26, 0.40))
	vbox.add_child(sep3)

	# Proceed button (hidden until all questions answered)
	_db_proceed_btn = Button.new()
	_db_proceed_btn.custom_minimum_size = Vector2(0, 48)
	_db_proceed_btn.add_theme_font_size_override("font_size", 15)
	_db_proceed_btn.add_theme_color_override("font_color", Color(0.72, 0.84, 0.96))
	var btn_style := StyleBoxFlat.new()
	btn_style.bg_color     = Color(0.06, 0.09, 0.14)
	btn_style.border_color = Color(0.28, 0.42, 0.62)
	btn_style.set_border_width_all(1)
	btn_style.content_margin_left  = 16.0
	btn_style.content_margin_right = 16.0
	_db_proceed_btn.add_theme_stylebox_override("normal",  btn_style)
	_db_proceed_btn.add_theme_stylebox_override("hover",   btn_style)
	_db_proceed_btn.add_theme_stylebox_override("pressed", btn_style)
	_db_proceed_btn.add_theme_stylebox_override("focus",   StyleBoxEmpty.new())
	_db_proceed_btn.visible = false
	_db_proceed_btn.pressed.connect(_on_db_proceed)
	vbox.add_child(_db_proceed_btn)


func _show_debrief() -> void:
	_db_questions = ai_overseer.get_debrief_questions(_current_shift)
	_db_q_idx     = 0

	_db_shift_lbl.text    = "END OF SHIFT %d  //  DEBRIEF PROTOCOL" % _current_shift
	_db_preamble_lbl.text = "◆  " + ai_overseer.get_debrief_preamble(_current_shift)
	_db_response_lbl.visible  = false
	_db_proceed_btn.visible   = false

	debrief_root.visible = true

	if _db_questions.is_empty():
		_finish_debrief()
	else:
		_show_db_question()


func _show_db_question() -> void:
	if _db_q_idx >= _db_questions.size():
		_finish_debrief()
		return

	var q: Dictionary = _db_questions[_db_q_idx]
	_db_progress_lbl.text  = "QUESTION  %d  /  %d" % [_db_q_idx + 1, _db_questions.size()]
	_db_question_lbl.text  = q.get("text", "")
	_db_response_lbl.visible = false

	# Clear old answer buttons
	for child in _db_answers_box.get_children():
		child.queue_free()

	var options: Array = q.get("options", [])
	for i in range(options.size()):
		var btn := Button.new()
		btn.text = options[i]
		btn.custom_minimum_size = Vector2(0, 42)
		btn.add_theme_font_size_override("font_size", 14)
		btn.add_theme_color_override("font_color", Color(0.80, 0.88, 0.96))

		var s := StyleBoxFlat.new()
		s.bg_color     = Color(0.05, 0.08, 0.13)
		s.border_color = Color(0.22, 0.33, 0.50)
		s.set_border_width_all(1)
		s.content_margin_left  = 14.0
		s.content_margin_right = 14.0
		btn.add_theme_stylebox_override("normal",  s)
		btn.add_theme_stylebox_override("focus",   StyleBoxEmpty.new())

		var sh := StyleBoxFlat.new()
		sh.bg_color     = Color(0.08, 0.14, 0.22)
		sh.border_color = Color(0.40, 0.58, 0.80)
		sh.set_border_width_all(1)
		sh.content_margin_left  = 14.0
		sh.content_margin_right = 14.0
		btn.add_theme_stylebox_override("hover",   sh)
		btn.add_theme_stylebox_override("pressed", sh)

		var captured_i := i
		btn.pressed.connect(func(): _on_db_answer(captured_i))
		_db_answers_box.add_child(btn)


func _on_db_answer(a_idx: int) -> void:
	# Disable all answer buttons while AI responds
	for child in _db_answers_box.get_children():
		child.disabled = true

	var response: String = ai_overseer.apply_debrief_answer(_current_shift, _db_q_idx, a_idx)
	if response != "":
		_db_response_lbl.text    = "◆  " + response
		_db_response_lbl.add_theme_color_override("font_color",
			_ai_voice_color(ai_overseer.current_phase))
		_db_response_lbl.visible = true

	_db_response_timer = 2.0   # pause before advancing to next question


func _finish_debrief() -> void:
	# Clear answer buttons
	for child in _db_answers_box.get_children():
		child.queue_free()
	_db_question_lbl.text    = ""
	_db_progress_lbl.text    = ""
	_db_response_lbl.visible = false

	if _current_shift >= SHIFT_COUNT:
		# Final shift — show ending
		var ending: String = ai_overseer.get_ending()
		_db_preamble_lbl.text = ai_overseer.get_ending_text()
		_db_shift_lbl.text    = "// " + ending.to_upper() + " //"
		_db_proceed_btn.text    = "◈  END TRANSMISSION"
		_db_proceed_btn.visible = true
	else:
		_db_proceed_btn.text    = "▶  PROCEED TO SHIFT %d" % (_current_shift + 1)
		_db_proceed_btn.visible = true


func _on_db_proceed() -> void:
	debrief_root.visible = false
	if _current_shift >= SHIFT_COUNT:
		# Game over — return to idle state
		_notify("◈  END OF WATCH", C_DIM)
	else:
		_start_next_shift()


func _dsep(parent: VBoxContainer) -> void:
	var sep := HSeparator.new()
	sep.add_theme_color_override("color", C_BORDER)
	parent.add_child(sep)


# ── DOSSIER REFRESH ──────────────────────────────────────────────
func _refresh_dossier() -> void:
	if not selected_item: return
	var item := selected_item
	var is_unplaced: bool = (selected_node == null and _current_tray_item == item)

	d_title.text = item.title
	d_classify.text = "  %s  //  AI THREAT %d  //  CONTAINMENT %d  " % [
		item.classification.to_upper(), item.ai_threat_level, item.containment_level
	]
	d_classify.add_theme_color_override("font_color",
		C_RED if item.ai_threat_level >= 3 else C_AMBER)

	var series_info := ""
	if item.series != "":
		series_info = "Series: %s  |  Vol. %d  |  " % [item.series, item.volume]
	d_meta.text = "%sCategory: %s\nOrigin: %s" % [series_info, item.category, item.origin]
	d_lore.text = item.lore_blurb
	d_research_bar.max_value = item.research_stages
	d_research_bar.value     = item.current_research_progress
	d_research_stage.text    = "Research stage  %d / %d" % [
		item.current_research_progress, item.research_stages
	]
	d_ai_sensitivity.text = "AI SENSITIVITY: %s" % (
		"⚠  CRITICAL — FEEDING THIS ITEM WILL ACCELERATE PHASE SHIFT"
		if item.is_ai_sensitive else "STANDARD"
	)
	d_ai_sensitivity.add_theme_color_override("font_color",
		C_RED if item.is_ai_sensitive else C_DIM)

	var annotation: String = ai_overseer.get_annotation(item)
	if annotation != "" and is_instance_valid(d_ai_annotation):
		d_ai_annotation.text    = "◆  " + annotation
		d_ai_annotation.visible = true
		d_ai_annotation.add_theme_color_override("font_color",
			_ai_voice_color(ai_overseer.current_phase))
	elif is_instance_valid(d_ai_annotation):
		d_ai_annotation.visible = false

	if is_unplaced:
		d_research_btn.text    = "▶  DRAG ARTIFACT TO CORRECT SHELF ROW"
		d_research_btn.disabled = true
		d_research_btn.add_theme_color_override("font_color", C_AMBER)
	else:
		var done := item.is_fully_researched()
		d_research_btn.disabled = done
		d_research_btn.text     = "✓  FULLY CATALOGUED — AI FED" if done else "▶  ADVANCE RESEARCH"
		d_research_btn.add_theme_color_override("font_color", C_DIM if done else C_GREEN)
