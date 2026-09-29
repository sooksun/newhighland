# -*- coding: utf-8 -*-
"""
ขั้นที่ 2/2 — รันต่อจาก utilities_1_fill_from_eval.py
1) จัดคอลัมน์ Q–V ใหม่: ไฟฟ้า | ระบบไฟฟ้า | น้ำประปา | แหล่งน้ำ | แหล่งข้อมูล | ปีที่สำรวจ (ตัวเลข)
2) สร้าง/แทนที่ชีต 'สรุปไฟฟ้าประปารายจังหวัด' (สูตร COUNTIFS อ้างชีต รายโรงเรียน)
รันซ้ำได้: ถ้าคอลัมน์ถูกจัดแล้ว (V1 = 'ปีที่สำรวจ') จะข้ามขั้น 1 และสร้างชีตสรุปใหม่
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

import re, collections
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter as L
from copy import copy

F = r'D:\laragon\www\newhighland\docs\ครู_DKSรวมนำเขาOBEC_Asset_รายโรงเรียน.xlsx'
wb = openpyxl.load_workbook(F)
ws = wb['รายโรงเรียน']
LAST = 1578  # แถวสุดท้ายของโรงเรียน (1579 = รวม)

# ---- 1) รายโรงเรียน: Q ไฟฟ้า | R ระบบไฟฟ้า | S น้ำประปา | T แหล่งน้ำ | U แหล่งข้อมูล | V ปีที่สำรวจ
rows = []
DONE = ws.cell(1, 22).value == 'ปีที่สำรวจ'  # จัดคอลัมน์ไปแล้ว → อ่านค่าเดิม
for r in range(2, LAST + 1):
    if DONE:
        rows.append(tuple(ws.cell(r, c).value for c in range(17, 23))); continue
    q, rr, s, t, u, v = (ws.cell(r, c).value for c in range(17, 23))
    assert s == v or s == 'สมมติฐาน', (r, s, v)
    m = re.match(r'(.*) ปี (\d{4})$', s or '')
    src, yr = (m.group(1), int(m.group(2))) if m else ('สมมติฐาน (ไม่พบใน DB)', None)
    rows.append((q, rr, t, u, src, yr))
    for k, val in enumerate(rows[-1]):
        ws.cell(r, 17 + k, val)
for k, h in enumerate(['ไฟฟ้า', 'ระบบไฟฟ้า (รายละเอียด)', 'น้ำประปา', 'แหล่งน้ำ (รายละเอียด)', 'แหล่งข้อมูล', 'ปีที่สำรวจ']):
    ws.cell(1, 17 + k, h)
for k, wdt in enumerate([24, 55, 18, 60, 28, 11]):
    ws.column_dimensions[L(17 + k)].width = wdt
for r in range(2, LAST + 1):
    ws.cell(r, 22).alignment = Alignment(horizontal='center')

years = sorted({x[5] for x in rows if x[5]})

# ---- 2) ชีตสรุป
ref = wb['สรุปรายจังหวัด']
provs = [ref.cell(i, 1).value for i in range(2, ref.max_row + 1) if ref.cell(i, 1).value != 'รวม']
NAME = 'สรุปไฟฟ้าประปารายจังหวัด'
if NAME in wb.sheetnames:
    del wb[NAME]
sh = wb.create_sheet(NAME, index=1)

hf, hfill = copy(ref.cell(1, 1).font), copy(ref.cell(1, 1).fill)
thin = Side(style='thin', color='BFBFBF')
box = Border(left=thin, right=thin, top=thin, bottom=thin)
ctr = Alignment(horizontal='center', vertical='center', wrap_text=True)

R = lambda col: f"รายโรงเรียน!${col}$2:${col}${LAST}"
PV, Q, S, U, V = R('C'), R('Q'), R('S'), R('U'), R('V')

# (หัวกลุ่ม, หัวคอลัมน์, สูตรแถว i)
cols = [
    (None, 'จังหวัด', None),
    (None, 'โรงเรียนทั้งหมด', lambda i: f'=COUNTIF({PV},$A{i})'),
    (None, 'พบข้อมูลใน DB', lambda i: f'=COUNTIFS({PV},$A{i},{V},">0")'),
    ('ไฟฟ้า', 'มีไฟฟ้าส่วนภูมิภาค', lambda i: f'=COUNTIFS({PV},$A{i},{Q},"มี",{V},">0")'),
    ('ไฟฟ้า', 'เฉพาะพลังงานทางเลือก', lambda i: f'=COUNTIFS({PV},$A{i},{Q},"มี (เฉพาะพลังงานทางเลือก)")'),
    ('ไฟฟ้า', 'ถือว่ามีไฟฟ้า (สมมติฐาน)', lambda i: f'=COUNTIFS({PV},$A{i},{U},"สมมติฐาน*")'),
    ('ไฟฟ้า', 'รวมมีไฟฟ้า', lambda i: f'=SUM(D{i}:F{i})'),
    ('น้ำประปา', 'มีน้ำประปา', lambda i: f'=COUNTIFS({PV},$A{i},{S},"มี")'),
    ('น้ำประปา', 'ไม่มีน้ำประปา', lambda i: f'=COUNTIFS({PV},$A{i},{S},"ไม่มี")'),
    ('น้ำประปา', 'ไม่พบข้อมูล', lambda i: f'=COUNTIFS({PV},$A{i},{S},"ไม่พบข้อมูล*")'),
    ('น้ำประปา', 'ร้อยละมีประปา (ของที่พบข้อมูล)', lambda i: f'=IF(H{i}+I{i}=0,"-",H{i}/(H{i}+I{i}))'),
] + [('ปีที่สำรวจ (จำนวนโรงเรียน)', f'ปี {y}', (lambda y: lambda i: f'=COUNTIFS({PV},$A{i},{V},{y})')(y)) for y in years] + [
    ('ปีที่สำรวจ (จำนวนโรงเรียน)', 'ปีล่าสุด', lambda i: f'=IF(C{i}=0,"-",SUMPRODUCT(MAX(({PV}=$A{i})*{V})))'),
]
NC = len(cols)

# หัวตาราง 2 แถว: กลุ่ม (merge) + ชื่อคอลัมน์
c = 1
while c <= NC:
    g = cols[c - 1][0]
    if g is None:
        sh.cell(1, c, cols[c - 1][1]); sh.merge_cells(start_row=1, start_column=c, end_row=2, end_column=c); c += 1
    else:
        e = c
        while e < NC and cols[e][0] == g: e += 1
        sh.cell(1, c, g); sh.merge_cells(start_row=1, start_column=c, end_row=1, end_column=e)
        for k in range(c, e + 1): sh.cell(2, k, cols[k - 1][1])
        c = e + 1
for rr in (1, 2):
    for k in range(1, NC + 1):
        cell = sh.cell(rr, k); cell.font, cell.fill, cell.alignment, cell.border = copy(hf), copy(hfill), ctr, box
sh.row_dimensions[2].height = 45

PCT = 11
for n, p in enumerate(provs):
    i = 3 + n
    sh.cell(i, 1, p).border = box
    for k in range(2, NC + 1):
        cell = sh.cell(i, k, cols[k - 1][2](i)); cell.border = box; cell.alignment = Alignment(horizontal='center')
        if k == PCT: cell.number_format = '0.0%'
last = 2 + len(provs); tot = last + 1
sh.cell(tot, 1, 'รวม')
for k in range(2, NC + 1):
    col = L(k)
    if k == PCT:
        f = f'=IF(H{tot}+I{tot}=0,"-",H{tot}/(H{tot}+I{tot}))'
    elif k == NC:
        f = f'=MAX({V})'
    else:
        f = f'=SUM({col}3:{col}{last})'
    cell = sh.cell(tot, k, f); cell.alignment = Alignment(horizontal='center')
    if k == PCT: cell.number_format = '0.0%'
for k in range(1, NC + 1):
    cell = sh.cell(tot, k); cell.font = Font(bold=True); cell.border = box
    cell.fill = PatternFill('solid', fgColor='DDEBF7')

notes = [
    'หมายเหตุ',
    '• ข้อมูลจากฐานข้อมูลคัดกรองโรงเรียนพื้นที่สูง (highland_eval ข้อ 7 น้ำ / ข้อ 8 ไฟฟ้า) และพื้นที่เกาะ (island_eval ข้อ 4.2 น้ำ / ข้อ 4.1 ไฟฟ้า) ใช้ปีล่าสุดที่มีคำตอบ',
    '• โรงเรียนที่ไม่พบข้อมูลใน DB ถือว่ามีไฟฟ้า (สมมติฐาน) ส่วนน้ำประปาระบุเป็น "ไม่พบข้อมูล"',
    '• "มีน้ำประปา" = ประปาหมู่บ้าน/ชุมชน, ประปา อปท., ประปาส่วนภูมิภาค ; ประปาภูเขา/แหล่งน้ำธรรมชาติ/บ่อ/บาดาล นับเป็น "ไม่มีน้ำประปา"',
    '• ร้อยละมีประปา คำนวณจากโรงเรียนที่พบข้อมูลเท่านั้น ; ตัวเลขคำนวณจากชีต รายโรงเรียน (คอลัมน์ Q–V) อัตโนมัติ',
]
for k, t in enumerate(notes):
    cell = sh.cell(tot + 2 + k, 1, t)
    if k == 0: cell.font = Font(bold=True)

sh.column_dimensions['A'].width = 18
for k in range(2, NC + 1):
    sh.column_dimensions[L(k)].width = 13
sh.freeze_panes = 'B3'
wb.calculation.fullCalcOnLoad = True
wb.save(F)

# ---- ตรวจค่าที่ควรได้ (คำนวณใน Python) เทียบกับสูตร
prov_of = [ws.cell(r, 3).value for r in range(2, LAST + 1)]
cnt = collections.Counter()
for p, (q, _, s, _, src, yr) in zip(prov_of, rows):
    cnt['n'] += 1; cnt['db'] += bool(yr)
    cnt['pea'] += (q == 'มี' and bool(yr)); cnt['alt'] += q == 'มี (เฉพาะพลังงานทางเลือก)'; cnt['assume'] += not yr
    cnt['tap'] += s == 'มี'; cnt['notap'] += s == 'ไม่มี'; cnt['nf'] += s.startswith('ไม่พบ')
    if yr: cnt[yr] += 1
print(dict(cnt)); print('years', years, 'provinces', len(provs), 'sheet cols', NC)
