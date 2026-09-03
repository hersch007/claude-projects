extends Node2D

var scroll_offset: float = 0.0

# ── Arcane Library Palette ───────────────────────────────────────────
const C_VOID     := Color(0.04, 0.03, 0.02)
const C_WALL_D   := Color(0.09, 0.07, 0.05)
const C_WALL_M   := Color(0.13, 0.10, 0.07)
const C_WALL_L   := Color(0.20, 0.15, 0.10)
const C_WOOD_D   := Color(0.17, 0.11, 0.05)
const C_WOOD_M   := Color(0.28, 0.19, 0.08)
const C_WOOD_L   := Color(0.46, 0.31, 0.13)
const C_WOOD_E   := Color(0.58, 0.40, 0.18)
const C_FELT     := Color(0.05, 0.10, 0.06)
const C_FELT_L   := Color(0.07, 0.13, 0.09)
const C_GOLD     := Color(0.58, 0.46, 0.17)
const C_GOLD_L   := Color(0.74, 0.62, 0.30)
const C_GOLD_D   := Color(0.35, 0.28, 0.09)
const C_STONE    := Color(0.16, 0.14, 0.12)
const C_STONE_L  := Color(0.23, 0.21, 0.18)
const C_STONE_D  := Color(0.10, 0.09, 0.08)
const C_CEIL_D   := Color(0.06, 0.05, 0.03)
const C_CEIL_M   := Color(0.11, 0.08, 0.05)
const C_CELL     := Color(0.04, 0.08, 0.05)

# ── Layout — must match main.gd ──────────────────────────────────────
const LEFT_SHELF_X:  float = 150.0
const RIGHT_SHELF_X: float = 1130.0
const SHELF_ROW_Y:   Array[float] = [143.0, 232.0, 328.0, 425.0, 504.0]
const ITEM_W:        float = 34.0
const ITEM_H:        float = 52.0
const ITEM_SPACING:  float = 40.0
const BOARD_H:       float = 10.0

const VW      := 1280.0
const VH      :=  720.0
const CEIL_H  :=   58.0
const FLOOR_Y :=  552.0

const LT_X1 :=   0.0
const LT_X2 := 290.0
const RT_X1 := 990.0
const RT_X2 :=  VW

# Ornate cabinet geometry
const CAB_TOP  :=  52.0   # top edge where cornice begins
const CAB_BOT  := 556.0   # bottom edge where plinth ends
const CORN_H   :=  26.0   # cornice height
const CORN_LIP :=   6.0   # cornice horizontal overhang past pilasters
const PLINTH_H :=  20.0   # base plinth height
const PIL_W    :=  15.0   # pilaster (side column) width
const RAIL_H   :=  11.0   # shelf rail (plank) height
const BRACE_H  :=   9.0   # decorative bracket height
const BRACE_W  :=  14.0   # decorative bracket width


func _draw() -> void:
	_draw_wall()
	_draw_ceiling()
	_draw_floor()
	_draw_aisle()
	_draw_cabinet_back(LT_X1, LT_X2)
	_draw_cabinet_back(RT_X1, RT_X2)
	_draw_item_cells(LT_X1, LT_X2, LEFT_SHELF_X)
	_draw_item_cells(RT_X1, RT_X2, RIGHT_SHELF_X)
	_draw_shelf_rails(LT_X1, LT_X2)
	_draw_shelf_rails(RT_X1, RT_X2)
	_draw_brackets(LT_X1, LT_X2)
	_draw_brackets(RT_X1, RT_X2)
	_draw_cabinet_frame(LT_X1, LT_X2)
	_draw_cabinet_frame(RT_X1, RT_X2)
	_draw_sconces()


# ── Base wall (near-black) ────────────────────────────────────────────
func _draw_wall() -> void:
	draw_rect(Rect2(0, 0, VW, VH), C_VOID)


# ── Dark coffered ceiling ─────────────────────────────────────────────
func _draw_ceiling() -> void:
	draw_rect(Rect2(0, 0, VW, CEIL_H), C_CEIL_D)
	# Coffer grid — vertical beam members
	var bx := 0.0
	while bx < VW:
		draw_rect(Rect2(bx, 0, 4, CEIL_H), C_CEIL_M)
		draw_rect(Rect2(bx + 1, 0, 2, CEIL_H), Color(C_CEIL_M.r + 0.04, C_CEIL_M.g + 0.03, C_CEIL_M.b + 0.01))
		bx += 120.0
	# Horizontal beam rails
	draw_rect(Rect2(0, 14, VW, 5), C_CEIL_M)
	draw_rect(Rect2(0, 14, VW, 2), Color(C_CEIL_M.r + 0.05, C_CEIL_M.g + 0.03, C_CEIL_M.b + 0.01))
	draw_rect(Rect2(0, 36, VW, 3), C_CEIL_M)
	# Bottom cornice line
	draw_rect(Rect2(0, CEIL_H - 5, VW, 5), C_VOID)
	draw_rect(Rect2(0, CEIL_H, VW, 2), Color(0, 0, 0, 0.75))


# ── Dark stone-flag floor ─────────────────────────────────────────────
func _draw_floor() -> void:
	draw_rect(Rect2(0, FLOOR_Y, VW, VH - FLOOR_Y), C_STONE)
	# Tile grid
	var tx := 0.0
	while tx < VW:
		draw_rect(Rect2(tx, FLOOR_Y, 1, VH - FLOOR_Y), C_STONE_D)
		tx += 72.0
	var ty := FLOOR_Y + 52.0
	while ty < VH:
		draw_rect(Rect2(0, ty, VW, 1), C_STONE_D)
		ty += 52.0
	# Floor edge shadow and highlight
	draw_rect(Rect2(0, FLOOR_Y, VW, 3), Color(0, 0, 0, 0.55))
	draw_rect(Rect2(0, FLOOR_Y + 3, VW, 2), C_STONE_L)


# ── Centre aisle — wainscoted back wall ──────────────────────────────
func _draw_aisle() -> void:
	var aw := RT_X1 - LT_X2
	# Upper wall
	draw_rect(Rect2(LT_X2, CEIL_H, aw, FLOOR_Y - CEIL_H), C_WALL_D)
	# Vertical panelling stiles (upper half)
	var upper_h := (FLOOR_Y - CEIL_H) * 0.48
	var px := LT_X2 + 70.0
	while px < RT_X1 - 40.0:
		draw_rect(Rect2(px, CEIL_H + 8, 2, upper_h - 10), C_WALL_M)
		draw_rect(Rect2(px + 1, CEIL_H + 8, 1, upper_h - 10), Color(C_WALL_L.r, C_WALL_L.g, C_WALL_L.b, 0.30))
		px += 140.0
	# Wainscoting cap rail
	var wain_y := CEIL_H + upper_h
	draw_rect(Rect2(LT_X2, wain_y - 4, aw, 7), C_WOOD_D)
	draw_rect(Rect2(LT_X2, wain_y - 4, aw, 2), C_WOOD_M)
	draw_rect(Rect2(LT_X2 + 6, wain_y, aw - 12, 2), C_GOLD_D)
	# Lower wainscoting field (darker)
	draw_rect(Rect2(LT_X2, wain_y + 2, aw, FLOOR_Y - wain_y - 2), C_WALL_M)
	var wpx := LT_X2 + 70.0
	while wpx < RT_X1 - 40.0:
		draw_rect(Rect2(wpx, wain_y + 8, 2, FLOOR_Y - wain_y - 14), C_WALL_D)
		wpx += 140.0
	# Deep shadow at cabinet–aisle junctions
	draw_rect(Rect2(LT_X2, CEIL_H, 14, FLOOR_Y - CEIL_H), Color(0, 0, 0, 0.38))
	draw_rect(Rect2(RT_X1 - 14, CEIL_H, 14, FLOOR_Y - CEIL_H), Color(0, 0, 0, 0.38))


# ── Dark green felt interior backing ─────────────────────────────────
func _draw_cabinet_back(x1: float, x2: float) -> void:
	var ix := x1 + PIL_W
	var iw := (x2 - x1) - PIL_W * 2.0
	var iy := CAB_TOP + CORN_H
	var ih := CAB_BOT - PLINTH_H - iy
	draw_rect(Rect2(ix, iy, iw, ih), C_FELT)
	# Subtle horizontal felt texture
	var fy := iy + 5.0
	while fy < iy + ih:
		draw_rect(Rect2(ix, fy, iw, 1), Color(C_FELT_L.r, C_FELT_L.g, C_FELT_L.b, 0.20))
		fy += 9.0
	# Inner edge depth shadows
	draw_rect(Rect2(ix, iy, 5, ih), Color(0, 0, 0, 0.30))
	draw_rect(Rect2(ix + iw - 5, iy, 5, ih), Color(0, 0, 0, 0.30))
	draw_rect(Rect2(ix, iy, iw, 10), Color(0, 0, 0, 0.42))


# ── Scrollable felt-lined item recesses ──────────────────────────────
func _draw_item_cells(x1: float, x2: float, base_x: float) -> void:
	var ix   := x1 + PIL_W
	var iw   := (x2 - x1) - PIL_W * 2.0
	var col0 := base_x - ITEM_W * 0.5 - scroll_offset
	var first_col := int(floor((ix - col0) / ITEM_SPACING)) - 1
	var last_col  := first_col + int(ceil(iw / ITEM_SPACING)) + 3

	for col in range(first_col, last_col):
		var cx: float = col0 + col * ITEM_SPACING
		if cx + ITEM_W < ix or cx > ix + iw:
			continue
		var dx := maxf(cx, ix)
		var dw := minf(cx + ITEM_W, ix + iw) - dx
		if dw <= 0.0:
			continue
		for row_y: float in SHELF_ROW_Y:
			var cy: float = row_y - ITEM_H * 0.5
			draw_rect(Rect2(dx, cy, dw, ITEM_H), C_CELL)
			# Top shadow (depth)
			draw_rect(Rect2(dx, cy, dw, 7), Color(0, 0, 0, 0.42))
			# Side shadows
			draw_rect(Rect2(dx, cy, 2, ITEM_H), Color(0, 0, 0, 0.28))
			draw_rect(Rect2(dx + dw - 2, cy, 2, ITEM_H), Color(0, 0, 0, 0.28))


# ── Horizontal wooden shelf rails ─────────────────────────────────────
func _draw_shelf_rails(x1: float, x2: float) -> void:
	var ix := x1 + PIL_W
	var iw := (x2 - x1) - PIL_W * 2.0
	# Top rail (header above first row)
	_draw_one_rail(ix, SHELF_ROW_Y[0] - ITEM_H * 0.5 - BOARD_H - 6.0, iw)
	# Rail at each shelf position
	for row_y: float in SHELF_ROW_Y:
		_draw_one_rail(ix, row_y - ITEM_H * 0.5 - BOARD_H, iw)
	# Bottom base rail
	_draw_one_rail(ix, SHELF_ROW_Y[SHELF_ROW_Y.size() - 1] + ITEM_H * 0.5 + 2.0, iw)


func _draw_one_rail(x: float, y: float, w: float) -> void:
	draw_rect(Rect2(x, y, w, RAIL_H), C_WOOD_M)
	draw_rect(Rect2(x, y, w, 2), C_WOOD_L)
	draw_rect(Rect2(x, y + 2, w, 1), C_WOOD_E)
	draw_rect(Rect2(x, y + RAIL_H - 2, w, 2), C_WOOD_D)
	draw_rect(Rect2(x, y + RAIL_H, w, 5), Color(0, 0, 0, 0.36))
	# Subtle grain line
	draw_rect(Rect2(x, y + 5, w, 1), Color(C_WOOD_L.r, C_WOOD_L.g, C_WOOD_L.b, 0.12))


# ── Decorative brackets under each rail ──────────────────────────────
func _draw_brackets(x1: float, x2: float) -> void:
	var ix := x1 + PIL_W
	var iw := (x2 - x1) - PIL_W * 2.0
	for row_y: float in SHELF_ROW_Y:
		var ry: float = row_y - ITEM_H * 0.5 - BOARD_H + RAIL_H
		for bx: float in [ix + 3.0, ix + iw * 0.5 - BRACE_W * 0.5, ix + iw - BRACE_W - 3.0]:
			draw_rect(Rect2(bx, ry, BRACE_W, BRACE_H), C_WOOD_D)
			draw_rect(Rect2(bx, ry, BRACE_W, 2), C_WOOD_M)
			draw_rect(Rect2(bx + 3, ry, BRACE_W - 6, 2), C_GOLD_D)


# ── Heavy ornate wooden cabinet frame ────────────────────────────────
func _draw_cabinet_frame(x1: float, x2: float) -> void:
	var bw  := x2 - x1
	var bh  := CAB_BOT - CAB_TOP
	var cx1 := x1 - CORN_LIP
	var cw  := bw + CORN_LIP * 2.0

	# Side pilasters
	_draw_pilaster(x1, CAB_TOP, bh)
	_draw_pilaster(x2 - PIL_W, CAB_TOP, bh)

	# Top cornice
	draw_rect(Rect2(cx1, CAB_TOP, cw, CORN_H), C_WOOD_D)
	draw_rect(Rect2(cx1, CAB_TOP, cw, 4), C_WOOD_L)
	draw_rect(Rect2(cx1, CAB_TOP + 4, cw, 2), C_WOOD_E)
	# Gold banding
	draw_rect(Rect2(cx1 + 5, CAB_TOP + 8, cw - 10, 3), C_GOLD)
	draw_rect(Rect2(cx1 + 5, CAB_TOP + 8, cw - 10, 1), C_GOLD_L)
	draw_rect(Rect2(cx1 + 5, CAB_TOP + 13, cw - 10, 2), C_GOLD_D)
	# Cornice underside shadow
	draw_rect(Rect2(cx1, CAB_TOP + CORN_H - 5, cw, 5), Color(0, 0, 0, 0.55))
	draw_rect(Rect2(cx1, CAB_TOP + CORN_H, cw + 5, 8), Color(0, 0, 0, 0.36))

	# Base plinth
	draw_rect(Rect2(cx1, CAB_BOT - PLINTH_H, cw, PLINTH_H), C_WOOD_D)
	draw_rect(Rect2(cx1, CAB_BOT - PLINTH_H, cw, 3), C_WOOD_M)
	draw_rect(Rect2(cx1 + 5, CAB_BOT - PLINTH_H + 5, cw - 10, 2), C_GOLD_D)
	draw_rect(Rect2(cx1, CAB_BOT - 4, cw, 4), C_VOID)
	# Plinth floor shadow
	draw_rect(Rect2(cx1 - 4, CAB_BOT, cw + 8, 6), Color(0, 0, 0, 0.32))

	# Gold corner rosettes at cornice/pilaster caps
	for rx: float in [x1 + PIL_W * 0.5 - 4.0, x2 - PIL_W * 0.5 - 4.0]:
		draw_rect(Rect2(rx, CAB_TOP + CORN_H - 8, 8, 8), C_GOLD_D)
		draw_rect(Rect2(rx + 2, CAB_TOP + CORN_H - 6, 4, 4), C_GOLD)
		draw_rect(Rect2(rx + 3, CAB_TOP + CORN_H - 5, 2, 2), C_GOLD_L)


func _draw_pilaster(px: float, py: float, ph: float) -> void:
	draw_rect(Rect2(px, py, PIL_W, ph), C_WOOD_D)
	# Left highlight
	draw_rect(Rect2(px, py, 3, ph), C_WOOD_M)
	draw_rect(Rect2(px + 1, py, 1, ph), C_WOOD_L)
	# Right shadow
	draw_rect(Rect2(px + PIL_W - 3, py, 3, ph), C_VOID)
	# Recessed panel groove
	draw_rect(Rect2(px + 4, py + 24, PIL_W - 8, ph - 48), Color(0, 0, 0, 0.20))
	draw_rect(Rect2(px + 4, py + 24, 1, ph - 48), Color(C_WOOD_M.r, C_WOOD_M.g, C_WOOD_M.b, 0.45))
	# Gold accent bars at cap and base
	draw_rect(Rect2(px + 3, py + 12, PIL_W - 6, 2), C_GOLD_D)
	draw_rect(Rect2(px + 3, py + ph - 14, PIL_W - 6, 2), C_GOLD_D)


# ── Ornate wall sconces ───────────────────────────────────────────────
func _draw_sconces() -> void:
	var aisle_mid := (LT_X2 + RT_X1) * 0.5
	var spread    := (RT_X1 - LT_X2) * 0.22
	_draw_sconce(aisle_mid - spread, 128.0)
	_draw_sconce(aisle_mid + spread, 128.0)


func _draw_sconce(cx: float, top_y: float) -> void:
	var arm_bot := top_y + 32.0

	# Wall backplate
	draw_rect(Rect2(cx - 9, top_y - 4, 18, 34), C_WOOD_D)
	draw_rect(Rect2(cx - 8, top_y - 3, 16, 2), C_WOOD_M)
	draw_rect(Rect2(cx - 6, top_y - 1, 12, 2), C_GOLD_D)

	# Sconce arm
	draw_rect(Rect2(cx - 4, top_y + 6, 8, arm_bot - top_y - 6), C_GOLD_D)
	draw_rect(Rect2(cx - 4, top_y + 6, 2, arm_bot - top_y - 6), C_GOLD)

	# Candle cup
	draw_rect(Rect2(cx - 7, arm_bot - 5, 14, 7), C_GOLD)
	draw_rect(Rect2(cx - 6, arm_bot - 3, 12, 6), C_GOLD_L)

	# Candle body
	draw_rect(Rect2(cx - 3, arm_bot - 18, 6, 14), Color(0.86, 0.84, 0.76))
	draw_rect(Rect2(cx - 3, arm_bot - 18, 6, 2), Color(0.92, 0.90, 0.82))

	# Wick
	draw_rect(Rect2(cx - 1, arm_bot - 24, 2, 7), Color(0.68, 0.52, 0.22))
	draw_circle(Vector2(cx, arm_bot - 25), 2, Color(1.0, 0.92, 0.55))

	# Glow halos (warm amber)
	draw_circle(Vector2(cx, arm_bot - 22), 60, Color(0.62, 0.36, 0.08, 0.06))
	draw_circle(Vector2(cx, arm_bot - 22), 36, Color(0.68, 0.40, 0.10, 0.12))
	draw_circle(Vector2(cx, arm_bot - 22), 18, Color(0.75, 0.48, 0.14, 0.22))
	draw_circle(Vector2(cx, arm_bot - 22),  8, Color(0.88, 0.62, 0.20, 0.45))

	# Downward light cone (wide soft)
	var cone1 := PackedVector2Array([
		Vector2(cx - 4, arm_bot - 20),
		Vector2(cx + 4, arm_bot - 20),
		Vector2(cx + 160.0, FLOOR_Y),
		Vector2(cx - 160.0, FLOOR_Y),
	])
	draw_polygon(cone1, PackedColorArray([
		Color(0.65, 0.40, 0.10, 0.18),
		Color(0.65, 0.40, 0.10, 0.18),
		Color(0.65, 0.40, 0.10, 0.00),
		Color(0.65, 0.40, 0.10, 0.00),
	]))
	# Inner cone (brighter)
	var cone2 := PackedVector2Array([
		Vector2(cx - 2, arm_bot - 20),
		Vector2(cx + 2, arm_bot - 20),
		Vector2(cx + 55.0, FLOOR_Y),
		Vector2(cx - 55.0, FLOOR_Y),
	])
	draw_polygon(cone2, PackedColorArray([
		Color(0.72, 0.46, 0.14, 0.28),
		Color(0.72, 0.46, 0.14, 0.28),
		Color(0.72, 0.46, 0.14, 0.00),
		Color(0.72, 0.46, 0.14, 0.00),
	]))
