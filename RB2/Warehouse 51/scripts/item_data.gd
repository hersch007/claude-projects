extends Resource
class_name ItemData

# === Core Identity ===
@export var id: String = ""
@export var title: String = ""
@export var category: String = ""
@export var series: String = ""
@export var volume: int = 1

# === Lore & Research ===
@export_multiline var lore_blurb: String = ""
@export var research_stages: int = 3
@export var current_research_progress: int = 0

# === AI Feeding System ===
@export var ai_feed_value: float = 1.0
@export var ai_threat_level: int = 1
@export var is_ai_sensitive: bool = false

# === Series Support ===
@export var is_series_key_item: bool = false
@export var series_feed_multiplier: float = 1.5

# === Containment & Classification ===
@export var classification: String = "Confidential"
@export var containment_level: int = 1
@export var origin: String = ""

# === Visuals ===
@export var mesh_path: String = "res://assets/items/"
@export var texture_path: String = ""

func _to_string() -> String:
	return "%s — %s Vol. %d" % [title, series, volume]

func get_dossier_header() -> String:
	return "%s\nSeries: %s Vol. %d | Classification: %s | AI Threat: %d" % [
		title, series, volume, classification, ai_threat_level
	]

func get_feed_amount(is_series_complete: bool = false) -> float:
	var amount := ai_feed_value
	if is_series_complete:
		amount *= series_feed_multiplier
	return amount

func is_fully_researched() -> bool:
	return current_research_progress >= research_stages

func can_feed_ai() -> bool:
	return is_fully_researched()
