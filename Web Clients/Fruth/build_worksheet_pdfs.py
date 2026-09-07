from pathlib import Path
from docx import Document
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak

ROOT = Path(r"C:\Users\richa\Documents\Claude Projects\Web Clients\Fruth")
OUT = ROOT / "output" / "resources"

PALETTE = {
    "ink": colors.HexColor("#283734"),
    "teal": colors.HexColor("#4F8F95"),
    "rust": colors.HexColor("#B86849"),
    "soft": colors.HexColor("#F5F0E8"),
}

def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(colors.HexColor("#D8CDBF"))
    canvas.line(doc.leftMargin, 0.55 * inch, letter[0] - doc.rightMargin, 0.55 * inch)
    canvas.setFont("Helvetica", 8.5)
    canvas.setFillColor(PALETTE["ink"])
    canvas.drawString(doc.leftMargin, 0.35 * inch, "Karen Welch Buttars, LMHC  |  karenwelchtherapist.com")
    canvas.drawRightString(letter[0] - doc.rightMargin, 0.35 * inch, str(doc.page))
    canvas.restoreState()

def build(source, filename):
    docx = Document(source)
    paragraphs = [p.text.strip() for p in docx.paragraphs if p.text.strip()]
    target = OUT / filename
    pdf = SimpleDocTemplate(str(target), pagesize=letter, rightMargin=.72*inch,
        leftMargin=.72*inch, topMargin=.65*inch, bottomMargin=.72*inch,
        title=paragraphs[0], author="Karen Welch Buttars, LMHC")
    styles = getSampleStyleSheet()
    title = ParagraphStyle("Title", parent=styles["Title"], fontName="Helvetica-Bold",
        fontSize=23, leading=27, textColor=PALETTE["ink"], alignment=TA_CENTER,
        spaceAfter=16)
    heading = ParagraphStyle("Heading", parent=styles["Heading2"], fontName="Helvetica-Bold",
        fontSize=13, leading=16, textColor=PALETTE["rust"], spaceBefore=11, spaceAfter=5)
    body = ParagraphStyle("Body", parent=styles["BodyText"], fontName="Helvetica",
        fontSize=10.5, leading=15, textColor=PALETTE["ink"], spaceAfter=7)
    bullet = ParagraphStyle("Bullet", parent=body, leftIndent=16, firstLineIndent=-10, bulletIndent=0,
        spaceAfter=5)
    signoff = ParagraphStyle("Signoff", parent=body, alignment=TA_CENTER, textColor=PALETTE["teal"],
        spaceBefore=10, fontName="Helvetica-Bold")
    story = [Paragraph(paragraphs[0], title), Spacer(1, 4)]
    section_names = {"An IFS Perspective", "Reflection Questions", "A Small Step This Week", "A Small Step to Try This Week"}
    for text in paragraphs[1:]:
        safe = text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
        is_numbered = len(text) > 3 and text[0].isdigit() and text[1] == "."
        if text in section_names or is_numbered:
            story.append(Paragraph(safe, heading))
        elif text.startswith("•"):
            story.append(Paragraph(safe[1:].strip(), bullet, bulletText="-"))
        elif text.startswith("Karen Welch Buttars"):
            story.append(Paragraph(safe.replace("\n", "<br/>"), signoff))
        else:
            story.append(Paragraph(safe.replace("\n", "<br/>"), body))
    pdf.build(story, onFirstPage=footer, onLaterPages=footer)
    return target

OUT.mkdir(parents=True, exist_ok=True)
print(build(r"C:\Users\richa\Downloads\5 signs (1).docx", "people-pleasing-worksheet.pdf"))
print(build(r"C:\Users\richa\Downloads\reflection-perfectionism (1).docx", "perfectionism-reflection.pdf"))
