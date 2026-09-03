extends Node

var ai_overseer = null

func _ready() -> void:
	ai_overseer = get_node_or_null("/root/AIOverseer")

# id -> ItemData
var item_registry: Dictionary = {}
# series_name -> Array of item ids belonging to that series
var series_registry: Dictionary = {}

signal item_registered(item: ItemData)
signal research_advanced(item: ItemData)
signal item_fully_researched(item: ItemData)
signal series_completed(series_name: String, items: Array)

func register_item(item: ItemData) -> void:
	if item_registry.has(item.id):
		return
	item_registry[item.id] = item

	if item.series != "":
		if not series_registry.has(item.series):
			series_registry[item.series] = []
		if not series_registry[item.series].has(item.id):
			series_registry[item.series].append(item.id)

	item_registered.emit(item)

func advance_research(item_id: String, amount: int = 1) -> void:
	var item: ItemData = item_registry.get(item_id)
	if not item:
		push_warning("ResearchManager: unknown item id '%s'" % item_id)
		return
	if item.is_fully_researched():
		return

	item.current_research_progress = mini(
		item.current_research_progress + amount,
		item.research_stages
	)
	research_advanced.emit(item)

	if item.is_fully_researched():
		_handle_fully_researched(item)

func _handle_fully_researched(item: ItemData) -> void:
	var series_now_complete := _is_series_complete(item.series)
	item_fully_researched.emit(item)

	if ai_overseer:
		ai_overseer.feed_item(item, series_now_complete)

	if series_now_complete:
		var members := _get_series_items(item.series)
		series_completed.emit(item.series, members)

func _is_series_complete(series_name: String) -> bool:
	if series_name == "":
		return false
	var ids: Array = series_registry.get(series_name, [])
	if ids.is_empty():
		return false
	for id in ids:
		var item: ItemData = item_registry.get(id)
		if not item or not item.is_fully_researched():
			return false
	return true

func _get_series_items(series_name: String) -> Array:
	var result: Array = []
	for id in series_registry.get(series_name, []):
		var item: ItemData = item_registry.get(id)
		if item:
			result.append(item)
	return result

func get_item(item_id: String) -> ItemData:
	return item_registry.get(item_id)

func get_all_items() -> Array:
	return item_registry.values()

func get_series_progress(series_name: String) -> String:
	var ids: Array = series_registry.get(series_name, [])
	var done := 0
	for id in ids:
		var item: ItemData = item_registry.get(id)
		if item and item.is_fully_researched():
			done += 1
	return "%d / %d" % [done, ids.size()]
