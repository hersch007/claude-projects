<?php
/**
 * Plugin Name: Quote Builder
 * Description: Quote Builder — database-backed pricing calculator.
 * Version: 8.5
 * Author: Start Advertising | RH Brashear
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SQS_VERSION', '8.5' );
define( 'SQS_DEFAULT_DATA_B64', 'eyJwYWNrYWdpbmciOnsiTm8gQmFncyI6MCwiU2luZ2xlIEJhZyI6MC4wMiwiRG91YmxlIEJhZyI6MC4wMywiVHJpcGxlIEJhZyI6MC4wNX0sInJlc2luRGVuc2l0eSI6eyJMRFBFIjowLjkyNCwiT2N0ZW5lIjowLjkyLCJOeWxvbiI6MS4xMywiQWRmbGV4IChQb2x5cHJvKSI6MC44OSwiVFItMTMwIChIRFBFKSI6MC45MzcsIkJsYWNrIENvbmR1Y3RpdmUiOjEuMSwiQmlvbWUgMzAwIjoxLjMyLCJBY2xhciI6Mi4wNzUzfSwiZm9ybXVsYUNvc3RzIjp7IkNGQi0xMDAwIjowLjk4LCJDRkItMjUwMChDRUxPKSI6MS4wNDc0OTk5OTk5OTk5OTk5LCJDRkItNTAwMChOeWxvbikiOjIuMTgsIkZDUi02MDAwKE55bG9uKSI6Mi4xOCwiRkNSLTEwMDAiOjEuMDM5MDAwMDAwMDAwMDAwMSwiRkNSLTEwMjAoRmVsbykiOjEuMzIyOCwiRklTLTEwMDAiOjAuNzE2NSwiRklTLTEwMTAgKFppcHBlcikiOjAuODg0NTIsIjc1JSBSZXBybyI6MC40MjQ5OTk5OTk5OTk5OTk5MywiRklTLTMwMDBCSyAoQmxhY2sgQ29uZCkiOjIuNywiRkNSLTEwMTAgKFppcHBlcikiOjEuMDY3NTAwMDAwMDAwMDAwMSwiRkNSLTUwMTAgKFN0ZWRpbSkiOjEuMTY3NSwiRkNSLTYwMTAoT0FTIE55bG9uKSI6Mi45NjN9LCJmb3JtdWxhT3B0aW9ucyI6WyJDRkItMTAwMCIsIkNGQi0yNTAwKENFTE8pIiwiQ0ZCLTUwMDAoTnlsb24pIiwiRkNSLTYwMDAoTnlsb24pIiwiRkNSLTEwMDAiLCJGQ1ItMTAyMChGZWxvKSIsIkZJUy0xMDAwIiwiRklTLTEwMTAgKFppcHBlcikiLCI3NSUgUmVwcm8iLCJGSVMtMzAwMEJLIChCbGFjayBDb25kKSIsIkZDUi0xMDEwIChaaXBwZXIpIiwiRklTLTUxMDBSRCAoUmVkIFBQKSIsIkZDUi01MDEwIChTdGVkaW0pIiwiRkNSLTYwMTAoT0FTIE55bG9uKSJdLCJmaWxtVHlwZXMiOlsiUEUvUFAgQ2xlYXIiLCJQRS9QUCBDb2xvciAoVGludCkiLCJQRS9QUCBDb2xvciAoT3BhcXVlKSIsIk55bG9uIiwiQmxhY2sgQ29uZHVjdGl2ZSIsIkhEUEUvTURQRSIsIlNwZWNpYWx0eSJdLCJyZXNpblR5cGVzIjpbIkxEUEUiLCJPY3RlbmUiLCJOeWxvbiIsIkFkZmxleCAoUG9seXBybykiLCJUUi0xMzAgKEhEUEUpIiwiQmxhY2sgQ29uZHVjdGl2ZSIsIkJpb21lIDMwMCIsIkFjbGFyIl0sInNldHVwV2lkdGhCdWNrZXRzIjpbMiw2LDEwLDE2LDI0LDM2LDQ4LDYwXSwicHJvZFdpZHRoQnVja2V0cyI6WzEsMiw0LDYsOCwxMCwxMiwxNiwxOCwyNCwzMCwzNiw0OCw2MF0sInppcHBlcldpZHRoQnVja2V0cyI6WzIsNCw2LDgsMTAsMTIsMTQsMTYsMTgsMjAsMjQsMjgsMzAsMzRdLCJzZXR1cE11bHRpcGxpZXIiOnsiUEUvUFAgQ2xlYXIiOjEsIlBFL1BQIENvbG9yIChUaW50KSI6MiwiUEUvUFAgQ29sb3IgKE9wYXF1ZSkiOjMsIk55bG9uIjoxLCJCbGFjayBDb25kdWN0aXZlIjozLCJIRFBFL01EUEUiOjIsIlNwZWNpYWx0eSI6NH0sInNldHVwSG91cnMiOnsiUEUvUFAgQ2xlYXIiOjAuNSwiUEUvUFAgQ29sb3IgKFRpbnQpIjoxLCJQRS9QUCBDb2xvciAoT3BhcXVlKSI6MS41LCJOeWxvbiI6MC41LCJCbGFjayBDb25kdWN0aXZlIjoxLjUsIkhEUEUvTURQRSI6MSwiU3BlY2lhbHR5IjoyfSwic2V0dXBMYnNUYWJsZSI6eyJQRS9QUCBDbGVhciI6WzEwLDIwLDMwLDQwLDc1LDkwLDE1MCwyNTBdLCJQRS9QUCBDb2xvciAoVGludCkiOlsyMCw0MCw2MCw4MCwxNTAsMTgwLDMwMCw1MDBdLCJQRS9QUCBDb2xvciAoT3BhcXVlKSI6WzMwLDYwLDkwLDEyMCwyMjUsMjcwLDQ1MCw3NTBdLCJOeWxvbiI6WzEwLDIwLDMwLDEwMCwyMDAsMjUwLCJOQSIsIk5BIl0sIkJsYWNrIENvbmR1Y3RpdmUiOlszMCw2MCw5MCwxMjAsMjI1LDI3MCw0NTAsNzUwXSwiSERQRS9NRFBFIjpbMjAsNDAsNjAsODAsMTUwLDE4MCwzMDAsNTAwXSwiU3BlY2lhbHR5IjpbNDAsODAsMTIwLDE2MCwzMDAsMzYwLDYwMCwxMDAwXX0sInNldHVwT3BzRXh0cnVzaW9uIjpbMSwxLDEsMSwxLjUsMiwyLDJdLCJpbmxpbmVBZGRpdGlvbmFsT3BzIjpbMSwxLDEsMSwxLDEsMSwiTi9BIl0sIm1pbmltdW1TZXR1cEZlZXMiOls3NSw3NSwxMDAsMTAwLDEwMCwxMDAsMjAwLDMwMF0sImV4dHJ1c2lvblJhdGVUYWJsZSI6eyIwLjAwMiI6WzMuNzUsNy41LDE1LDIwLDI1LDMwLDQwLDUwLDcwLDEwMCwxNDAsMTUwLDIwMCwyODBdLCIwLjAwMyI6WzQuMjE4NzUsOC40Mzc1LDE2Ljg3NSwyMi41LDI4LjEyNSwzMy43NSw0NSw1Ni4yNSw3OC43NSwxMTIuNSwxNTcuNSwxNjguNzUsMjI1LDMxNV0sIjAuMDA0IjpbNC42ODc1LDkuMzc1LDE4Ljc1LDI1LDMxLjI1LDM3LjUsNTAsNjIuNSw4Ny41LDEyNSwxNzUsMTg3LjUsMjUwLDM1MF0sIjAuMDA1IjpbNS4xMDExMDI5NDExNzY0NzEsMTAuMjAyMjA1ODgyMzUyOTQyLDIwLjQwNDQxMTc2NDcwNTg4NCwyNy4yMDU4ODIzNTI5NDExNzgsMzQuMDA3MzUyOTQxMTc2NDcsNDAuODA4ODIzNTI5NDExNzcsNTQuNDExNzY0NzA1ODgyMzU1LDY4LjAxNDcwNTg4MjM1Mjk0LDk1LjIyMDU4ODIzNTI5NDEyLDEzNi4wMjk0MTE3NjQ3MDU4OCwxOTAuNDQxMTc2NDcwNTg4MjMsMjA0LjA0NDExNzY0NzA1ODg0LDI3Mi4wNTg4MjM1Mjk0MTE3NywzNzVdLCIwLjAwNiI6WzUuNTE0NzA1ODgyMzUyOTQxLDExLjAyOTQxMTc2NDcwNTg4MiwyMi4wNTg4MjM1Mjk0MTE3NjQsMjkuNDExNzY0NzA1ODgyMzU1LDM2Ljc2NDcwNTg4MjM1Mjk0LDQ0LjExNzY0NzA1ODgyMzUzLDU4LjgyMzUyOTQxMTc2NDcxLDczLjUyOTQxMTc2NDcwNTg4LDEwMi45NDExNzY0NzA1ODgyMywxNDcuMDU4ODIzNTI5NDExNzcsMjA1Ljg4MjM1Mjk0MTE3NjQ2LDIyMC41ODgyMzUyOTQxMTc2NSwyOTQuMTE3NjQ3MDU4ODIzNTQsNDAwXX0sImlubGluZVJhdGVUYWJsZSI6eyIwLjAwMiI6WzMuNzUsNy41LDE1LDIwLDI1LDMwLDQwLDUwLDcwLDEwMCwxNDAsMTUwLDIwMCwyODBdLCIwLjAwMyI6WzQuMjE4NzUsOC40Mzc1LDE2Ljg3NSwyMi41LDI4LjEyNSwzMy43NSw0NSw1Ni4yNSw3OC43NSwxMTIuNSwxNTcuNSwxNjguNzUsMjI1LDMxNV0sIjAuMDA0IjpbNC42ODc1LDkuMzc1LDE4Ljc1LDI1LDMxLjI1LDM3LjUsNTAsNjIuNSw4Ny41LDEyNSwxNzUsMTg3LjUsMjUwLDM1MF0sIjAuMDA1IjpbNS4xMDExMDI5NDExNzY0NzEsMTAuMjAyMjA1ODgyMzUyOTQyLDIwLjQwNDQxMTc2NDcwNTg4NCwyNy4yMDU4ODIzNTI5NDExNzgsMzQuMDA3MzUyOTQxMTc2NDcsNDAuODA4ODIzNTI5NDExNzcsNTQuNDExNzY0NzA1ODgyMzU1LDY4LjAxNDcwNTg4MjM1Mjk0LDk1LjIyMDU4ODIzNTI5NDEyLDEzNi4wMjk0MTE3NjQ3MDU4OCwxOTAuNDQxMTc2NDcwNTg4MjMsMjA0LjA0NDExNzY0NzA1ODg0LDI3Mi4wNTg4MjM1Mjk0MTE3NywzNzVdLCIwLjAwNiI6WzUuNTE0NzA1ODgyMzUyOTQxLDExLjAyOTQxMTc2NDcwNTg4MiwyMi4wNTg4MjM1Mjk0MTE3NjQsMjkuNDExNzY0NzA1ODgyMzU1LDM2Ljc2NDcwNTg4MjM1Mjk0LDQ0LjExNzY0NzA1ODgyMzUzLDU4LjgyMzUyOTQxMTc2NDcxLDczLjUyOTQxMTc2NDcwNTg4LDEwMi45NDExNzY0NzA1ODgyMywxNDcuMDU4ODIzNTI5NDExNzcsMjA1Ljg4MjM1Mjk0MTE3NjQ2LDIyMC41ODgyMzUyOTQxMTc2NSwyOTQuMTE3NjQ3MDU4ODIzNTQsNDAwXX0sInppcHBlclF0eVBlckhvdXIiOlsxNTYyLjUsMTg3NSwxODc1LDE1MDAsMTUwMCwxMjUwLDkzNy41LDgxMi41LDgxMi41LDcxMi41LDU5My43NSw1OTMuNzUsNDM3LjUsNDM3LjVdLCJsYWJvclJhdGVzIjp7InR1YmluZyI6MjUsImlubGluZSI6MjMuNzUsInppcHBlckV4dHJ1c2lvbiI6MjUsInppcHBlckNvbnZlcnNpb24iOjIzLjc1fSwiZGVmYXVsdHMiOnsidHViaW5nIjp7ImZpbG1UeXBlIjoiUEUvUFAgQ29sb3IgKFRpbnQpIiwicmVzaW5UeXBlIjoiTERQRSIsImZvcm11bGEiOiJGQ1ItMTAwMCIsIndpZHRoIjo0MCwibGVuZ3RoRnQiOjUwMCwiZ2F1Z2UiOjAuMDA0LCJxdHkiOjEwLCJyZXNpbkNvc3QiOjEuMDQsIm9wZXJhdG9ycyI6MSwic2NyYXBSYXRlIjowLjIsInBhY2thZ2luZyI6IkRvdWJsZSBCYWciLCJjdXN0b21QYWNrYWdpbmdGZWUiOjAsInNwZWNpYWx0eUNoYXJnZSI6MCwiY3VzdG9tU2V0dXBDaGFyZ2UiOjAsInByb2ZpdE1hcmdpbiI6MC40LCJ1cGNoYXJnZSI6MC4yLCJ0YXJnZXRVbml0UHJpY2UiOjgxLjE1LCJvdmVyaGVhZFBjdCI6MC4xNX0sImlubGluZSI6eyJmaWxtVHlwZSI6IlBFL1BQIENvbG9yIChPcGFxdWUpIiwicmVzaW5UeXBlIjoiQWRmbGV4IChQb2x5cHJvKSIsImZvcm11bGEiOiJDRkItMTAwMCIsIndpZHRoIjozMCwibGVuZ3RoSW4iOjM2LCJnYXVnZSI6MC4wMDQsInF0eSI6NTAwMCwicmVzaW5Db3N0IjoxLjkzLCJvcGVyYXRvcnMiOjIuNSwic2NyYXBSYXRlIjowLjIsInBhY2thZ2luZyI6Ik5vIEJhZ3MiLCJjdXN0b21QYWNrYWdpbmdGZWUiOjAsImVuY2xvc3VyZUNoYXJnZSI6MCwic3BlY2lhbHR5Q2hhcmdlIjowLCJjdXN0b21TZXR1cENoYXJnZSI6NDUwLCJwcm9maXRNYXJnaW4iOjAuNSwidXBjaGFyZ2UiOjAsInRhcmdldFVuaXRQcmljZSI6NDE5MCwib3ZlcmhlYWRQY3QiOjAuMTV9LCJ6aXBwZXIiOnsiZmlsbVR5cGUiOiJQRS9QUCBDb2xvciAoVGludCkiLCJyZXNpblR5cGUiOiJMRFBFIiwiZm9ybXVsYSI6IkZDUi0xMDAwIiwid2lkdGgiOjEwLCJsZW5ndGhJbiI6MTIsImxpcEluIjoxLCJnYXVnZSI6MC4wMDQsInF0eSI6MTAwMCwicmVzaW5Db3N0IjoxLjM1LCJleHRydXNpb25PcGVyYXRvcnMiOjEsImNvbnZlcnNpb25UeXBlIjoiMXVwIFppcHBlciIsInppcHBlckNvc3RQZXJGdCI6MC4wMSwiY29udmVyc2lvbk9wZXJhdG9ycyI6MiwiZXh0cnVzaW9uU2NyYXBSYXRlIjowLjEsImNvbnZlcnNpb25TY3JhcFJhdGUiOjAuMSwidG90YWxTY3JhcFJhdGUiOjAuMTUsInBhY2thZ2luZyI6IkRvdWJsZSBCYWciLCJjdXN0b21QYWNrYWdpbmdGZWUiOjAsImVuY2xvc3VyZUNoYXJnZSI6MCwic3BlY2lhbHR5Q2hhcmdlIjowLCJjdXN0b21FeHRydXNpb25TZXR1cENoYXJnZSI6MCwiY3VzdG9tQ29udmVyc2lvblNldHVwQ2hhcmdlIjowLCJwcm9maXRNYXJnaW4iOjAuNCwiY2xlYW5yb29tVXBjaGFyZ2UiOjAuMSwidGFyZ2V0VW5pdFByaWNlIjo1MDQsIm92ZXJoZWFkUGN0IjowLjE1fX19' );

function sqs_pricing_calculator_default_data() {
    $json = base64_decode( SQS_DEFAULT_DATA_B64 );
    $data = json_decode( $json, true );
    if ( ! is_array( $data ) ) {
        $data = array();
    }

    // ── Product Codes ──────────────────────────────────────────────────────────
    // Merged here so the base64 constant stays unchanged.
    // Each entry: { code, desc }  — editable in Admin Control Panel.
    if ( ! isset( $data['productCodes'] ) ) {
        $data['productCodes'] = array(
            // LDPE
            array( 'code' => 'FCR-1000',  'desc' => 'Fruth Code' ),
            array( 'code' => 'CFB1000',   'desc' => 'LDPE' ),
            array( 'code' => 'CFB1100',   'desc' => 'LLDPE' ),
            array( 'code' => 'CFB1005',   'desc' => 'LDPE Black' ),
            array( 'code' => 'CFB1600',   'desc' => 'LDPE Square Bottom Cover' ),
            array( 'code' => 'CFB1603',   'desc' => 'LDPE PAS Square Bottom Cover' ),
            array( 'code' => 'CFB2504',   'desc' => 'Pink A/S Low Outgassing LDPE' ),
            array( 'code' => 'CFB1006',   'desc' => 'Blue LDPE' ),
            array( 'code' => 'CFB9609',   'desc' => 'LDPE White Antistatic' ),
            array( 'code' => 'CFB9610',   'desc' => 'Black A/S LDPE' ),
            array( 'code' => 'CFB1011',   'desc' => 'Silo LDPE w/ Adds' ),
            array( 'code' => 'CFB1012',   'desc' => 'CFB1000 w/ AB' ),
            array( 'code' => 'CFB2503',   'desc' => 'Clear LAM LDPE' ),
            array( 'code' => 'CFB2500',   'desc' => 'CELO (formerly ULO)' ),
            array( 'code' => 'CFB9603',   'desc' => 'LDPE Clear Antistatic' ),
            array( 'code' => 'CFB9604',   'desc' => 'LDPE Pink Antistatic' ),
            array( 'code' => 'CFB9605',   'desc' => 'LDPE Blue Antistatic' ),
            array( 'code' => 'CFB9606',   'desc' => 'LDPE Green Antistatic' ),
            array( 'code' => 'CFB1090',   'desc' => 'Ultraclear LDPE' ),
            array( 'code' => 'CFB1095',   'desc' => 'Ultraclear II' ),
            array( 'code' => 'CFB1900',   'desc' => 'LDPE Industrial Film' ),
            // LDPE Zipper
            array( 'code' => 'CFB1001',   'desc' => 'LDPE Zipper' ),
            array( 'code' => 'CFB1030',   'desc' => 'CLR UVI LDPE Zipper' ),
            array( 'code' => 'CFB1040',   'desc' => 'LDPE Black UVI Zipper' ),
            array( 'code' => 'CFB1061',   'desc' => 'Blue Zipper (LDPE)' ),
            // Anti-Stat
            array( 'code' => 'CFB9601',   'desc' => 'Pink A/S Zipper' ),
            array( 'code' => 'CFB9602',   'desc' => 'LDPE Clear A/S Zipper' ),
            array( 'code' => 'CFB9608',   'desc' => 'LDPE Black A/S Zipper' ),
            // HDPE / MDPE
            array( 'code' => 'CFB3000',   'desc' => 'MDPE' ),
            array( 'code' => 'CFB4000',   'desc' => 'HDPE' ),
            array( 'code' => 'CFB4001',   'desc' => 'HDPE Zipper' ),
            array( 'code' => 'CFB4003',   'desc' => 'HDPE Clear Antistatic' ),
            array( 'code' => 'CFB4005',   'desc' => 'Pink HDPE' ),
            array( 'code' => 'CFB4100',   'desc' => 'HDPE/LDPE' ),
            // Nylon
            array( 'code' => 'CFB5000',   'desc' => 'Clear Nylon' ),
            array( 'code' => 'CFB5009',   'desc' => 'Clear Nylon II' ),
            array( 'code' => 'CFB5100',   'desc' => 'Orange Antistatic Nylon' ),
            array( 'code' => 'CFB5200',   'desc' => 'Clear A/S Nylon Zipper' ),
            array( 'code' => 'CFB5500',   'desc' => 'Poly/Nylon (Compos-A-Clean)' ),
            array( 'code' => 'CFB5510',   'desc' => 'BIAX Nylon/EVOH/Poly' ),
            array( 'code' => 'CFB5600',   'desc' => 'Polyester/Poly' ),
            array( 'code' => 'CFB5700',   'desc' => 'High Barrier ALOX' ),
            array( 'code' => 'CFB5800',   'desc' => 'Clear Nylon Oven Bag' ),
            // Barrier / Specialty
            array( 'code' => 'CFB6000',   'desc' => 'A/S Moisture Barrier Nylon/Foil/Poly' ),
            array( 'code' => 'CFB6500',   'desc' => 'Static Shield (CP STAT 100)' ),
            array( 'code' => 'CFB6600',   'desc' => 'Static Shield Zipper Bags (CP STAT 100)' ),
            array( 'code' => 'CFB6700',   'desc' => 'PFP PET/Foil/Poly Barrier Film 4mil' ),
            array( 'code' => 'CFB6710',   'desc' => 'EMI Shielding MIL-PRF-81705-T3-C2 (CP STAT 100M)' ),
            array( 'code' => 'CFB6720',   'desc' => 'EMI Shielding MIL-PRF-81705-T1-C1 (Cadpack ESD)' ),
            array( 'code' => 'CFB6800',   'desc' => 'Poly/Aclar Barrier MIL-PRF-22191-T1 (Cadpak CL)' ),
            array( 'code' => 'CFB6810',   'desc' => 'Foil Barrier MIL-PRF-131-C1 (Cadpak PL)' ),
            // CELO / Aclar
            array( 'code' => 'CFB7000',   'desc' => 'Aclar 22a 2mil (Hydroblock 22a)' ),
            array( 'code' => 'CFB7100',   'desc' => 'FR 5500 POLY/ACLAR' ),
            // Cleantuff
            array( 'code' => 'CT100',     'desc' => 'LDPE Cleantuff' ),
            array( 'code' => 'CT101',     'desc' => 'LDPE Cleantuff Blue Antistatic' ),
            array( 'code' => 'CT102',     'desc' => 'LDPE Cleantuff Pink Antistatic' ),
            array( 'code' => 'CT103',     'desc' => 'LDPE Cleantuff Clear Antistatic' ),
            array( 'code' => 'CT104',     'desc' => 'LDPE Cleantuff Orange' ),
            array( 'code' => 'CT108',     'desc' => 'Red Cleantuff' ),
            array( 'code' => 'CT110',     'desc' => 'Green Cleantuff' ),
            array( 'code' => 'CT150',     'desc' => 'Super Cleantuff' ),
            array( 'code' => 'CT200',     'desc' => 'HDPE Cleantuff' ),
            array( 'code' => 'CT300',     'desc' => 'Max Strength Cleantuff' ),
            array( 'code' => 'CT1001',    'desc' => 'Cleantuff Zipper Bags' ),
            array( 'code' => 'CFB8000',   'desc' => 'EZ-Tear Cleantuff Poly' ),
            // Other / Specialty
            array( 'code' => 'CFS50',     'desc' => 'Corning Special' ),
            array( 'code' => 'CFB8100',   'desc' => 'Green Nuclear Film' ),
            // Tyvek
            array( 'code' => 'CTY100',    'desc' => 'TYVEK Pouch Unprinted Uncoated (1073B/PETPE)' ),
            array( 'code' => 'CTY101',    'desc' => 'TYVEK Header Bag Unprinted Uncoated (1073B/PETPE)' ),
            array( 'code' => 'CTY200',    'desc' => 'TYVEK Printed Pouch Uncoated (1073B/PETPE)' ),
            array( 'code' => 'CTY201',    'desc' => 'TYVEK Printed Header Bag Uncoated (1073B/PETPE)' ),
            array( 'code' => 'CTY300',    'desc' => 'TYVEK Header Bag Unprinted Coated (1073B/PE)' ),
            array( 'code' => 'CTY400',    'desc' => 'TYVEK Header Bag Printed Coated (1073B/PE)' ),
            array( 'code' => 'CTY500',    'desc' => 'TYVEK Coated Roll Unprinted 1073B' ),
            array( 'code' => 'CTY600',    'desc' => 'TYVEK Roll Printed 1073B' ),
            array( 'code' => 'CTYV100',   'desc' => 'TYVEK Pouch Unprinted Uncoated (1073B/4mil PAPE)' ),
            // Medical
            array( 'code' => 'CPET100',   'desc' => 'Multi-Layer Film Unprinted (12um PET/PE)' ),
            array( 'code' => 'CPET101',   'desc' => 'Multi-Layer Film Printed (12um PET/PE)' ),
            array( 'code' => 'CPET200',   'desc' => 'Multi-Layer Foil Unprinted (12um PET/Foil/LLDPE)' ),
            array( 'code' => 'CPET201',   'desc' => 'Multi-Layer Foil Printed (12um PET/Foil/LLDPE)' ),
            array( 'code' => 'CPAP100',   'desc' => 'Medical Paper Roll 60 gsm' ),
            array( 'code' => 'CPAP200',   'desc' => 'Medical Paper Roll 70 gsm' ),
        );
    }

    // Ensure productCode exists in each product's defaults
    foreach ( array( 'tubing', 'inline', 'zipper' ) as $pt ) {
        if ( isset( $data['defaults'][ $pt ] ) && ! array_key_exists( 'productCode', $data['defaults'][ $pt ] ) ) {
            $data['defaults'][ $pt ]['productCode'] = '';
        }
    }

    return $data;
}


function sqs_pricing_calculator_clean_data_key( $key ) {
    return preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $key );
}

function sqs_pricing_calculator_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sqs_pricing_data';
}

function sqs_pricing_calculator_quotes_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sqs_quotes';
}

function sqs_pricing_calculator_quote_items_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sqs_quote_items';
}

function sqs_pricing_calculator_revision_from_index( $index ) {
    $index = max( 1, absint( $index ) );
    $letters = '';
    while ( $index > 0 ) {
        $index--;
        $letters = chr( 65 + ( $index % 26 ) ) . $letters;
        $index = intdiv( $index, 26 );
    }
    return 'Rev ' . $letters;
}

function sqs_pricing_calculator_user_can_quote_ajax() {
    sqs_pricing_calculator_maybe_start_session();
    return ( ! empty( $_SESSION['sqs_calc_sales_logged_in'] ) && true === $_SESSION['sqs_calc_sales_logged_in'] ) || current_user_can( 'manage_options' );
}

function sqs_pricing_calculator_clean_quote_status( $status ) {
    $status = sanitize_text_field( (string) $status );
    return in_array( $status, array( 'Draft', 'Final', 'Archived' ), true ) ? $status : 'Draft';
}

function sqs_pricing_calculator_generate_quote_number( $insert_id ) {
    return 'SQS-' . gmdate( 'Y' ) . '-' . str_pad( absint( $insert_id ), 5, '0', STR_PAD_LEFT );
}

function sqs_pricing_calculator_activate() {
    global $wpdb;
    $table = sqs_pricing_calculator_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        data_key varchar(100) NOT NULL,
        data_label varchar(191) NOT NULL,
        data_json longtext NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY data_key (data_key)
    ) $charset_collate;";

    dbDelta( $sql );

    $quotes_table = sqs_pricing_calculator_quotes_table_name();
    $quote_items_table = sqs_pricing_calculator_quote_items_table_name();

    $quotes_sql = "CREATE TABLE $quotes_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        quote_number varchar(40) NOT NULL DEFAULT '',
        revision_number int(11) NOT NULL DEFAULT 1,
        revision_label varchar(20) NOT NULL DEFAULT 'Rev A',
        status varchar(20) NOT NULL DEFAULT 'Draft',
        product_type varchar(50) NOT NULL DEFAULT '',
        customer_name varchar(191) NOT NULL DEFAULT '',
        company_name varchar(191) NOT NULL DEFAULT '',
        total_sales decimal(15,2) NOT NULL DEFAULT 0.00,
        quote_json longtext NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY quote_number (quote_number),
        KEY status (status),
        KEY updated_at (updated_at)
    ) $charset_collate;";

    $quote_items_sql = "CREATE TABLE $quote_items_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        quote_id bigint(20) unsigned NOT NULL,
        line_number int(11) NOT NULL DEFAULT 1,
        product_type varchar(50) NOT NULL DEFAULT '',
        description text NOT NULL,
        quantity decimal(15,4) NOT NULL DEFAULT 0.0000,
        unit_price decimal(15,4) NOT NULL DEFAULT 0.0000,
        line_total decimal(15,2) NOT NULL DEFAULT 0.00,
        item_json longtext NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY quote_id (quote_id)
    ) $charset_collate;";

    dbDelta( $quotes_sql );
    dbDelta( $quote_items_sql );
    sqs_pricing_calculator_seed_defaults( false );

    if ( ! get_option( 'sqs_calc_frontend_user' ) ) {
        update_option( 'sqs_calc_frontend_user', 'start' );
    }
    if ( ! get_option( 'sqs_calc_frontend_pass_hash' ) ) {
        update_option( 'sqs_calc_frontend_pass_hash', wp_hash_password( 'ChangeMe123!' ) );
    }
}

register_activation_hook( __FILE__, 'sqs_pricing_calculator_activate' );

function sqs_pricing_calculator_labels() {
    return array(
        'packaging'             => 'Packaging',
        'resinDensity'          => 'Resin Density',
        'formulaCosts'          => 'Formula Costs',
        'formulaOptions'        => 'Formula Options',
        'filmTypes'             => 'Film Types',
        'resinTypes'            => 'Resin Types',
        'setupWidthBuckets'     => 'Setup Width Buckets',
        'prodWidthBuckets'      => 'Production Width Buckets',
        'zipperWidthBuckets'    => 'Zipper Width Buckets',
        'setupMultiplier'       => 'Setup Multipliers',
        'setupHours'            => 'Setup Hours',
        'setupLbsTable'         => 'Setup Pounds Table',
        'setupOpsExtrusion'     => 'Setup Operators - Extrusion',
        'inlineAdditionalOps'   => 'Inline Additional Operators',
        'minimumSetupFees'      => 'Minimum Setup Fees',
        'extrusionRateTable'    => 'Extrusion Rate Table',
        'inlineRateTable'       => 'Inline Rate Table',
        'zipperQtyPerHour'      => 'Zipper Quantity Per Hour',
        'laborRates'            => 'Labor Rates',
        'defaults'              => 'Calculator Defaults',
        'productCodes'          => 'Product Codes',
    );
}

function sqs_pricing_calculator_seed_defaults( $overwrite = false ) {
    global $wpdb;
    $table = sqs_pricing_calculator_table_name();
    $defaults = sqs_pricing_calculator_default_data();
    $labels = sqs_pricing_calculator_labels();

    foreach ( $defaults as $key => $value ) {
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE data_key = %s", $key ) );
        if ( $exists && ! $overwrite ) {
            continue;
        }
        $row = array(
            'data_key'   => $key,
            'data_label' => isset( $labels[ $key ] ) ? $labels[ $key ] : $key,
            'data_json'  => wp_json_encode( $value, JSON_PRETTY_PRINT ),
            'updated_at' => current_time( 'mysql' ),
        );
        if ( $exists ) {
            $wpdb->update( $table, $row, array( 'data_key' => $key ) );
        } else {
            $wpdb->insert( $table, $row );
        }
    }
}

function sqs_pricing_calculator_get_data() {
    global $wpdb;
    $table = sqs_pricing_calculator_table_name();
    $data = sqs_pricing_calculator_default_data();

    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $exists !== $table ) {
        return $data;
    }

    $rows = $wpdb->get_results( "SELECT data_key, data_json FROM $table", ARRAY_A );
    foreach ( $rows as $row ) {
        $decoded = json_decode( $row['data_json'], true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            $data[ $row['data_key'] ] = $decoded;
        }
    }
    return $data;
}

function sqs_pricing_calculator_admin_menu() {
    add_menu_page(
        'Quote Builder',
        'Quote Builder',
        'manage_options',
        'sqs-pricing-calculator',
        'sqs_pricing_calculator_admin_page',
        'dashicons-chart-area',
        58
    );

    // Rename the default first submenu so the sidebar is clearer.
    add_submenu_page(
        'sqs-pricing-calculator',
        'Pricing Tables',
        'Pricing Tables',
        'manage_options',
        'sqs-pricing-calculator',
        'sqs_pricing_calculator_admin_page'
    );
}
add_action( 'admin_menu', 'sqs_pricing_calculator_admin_menu' );

function sqs_pricing_calculator_portal_url() {
    global $wpdb;
    $page_id = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'page' AND post_status = 'publish'
         AND post_content LIKE '%[sqs_portal]%'
         LIMIT 1"
    );
    if ( $page_id ) {
        return get_permalink( (int) $page_id );
    }
    return home_url( '/' );
}


function sqs_pricing_calculator_logout_url() {
    return sqs_pricing_calculator_portal_url();
}

function sqs_pricing_calculator_external_admin_link_page( $title, $target_url ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'sales-quote-system' ) );
    }
    $target_url = esc_url( $target_url );
    echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
    echo '<p>This screen opens outside the WordPress admin frame so the calculator layout has full width.</p>';
    echo '<p><a class="button button-primary button-hero" href="' . $target_url . '">Open ' . esc_html( $title ) . '</a></p>';
    echo '<script>window.location.href=' . wp_json_encode( $target_url ) . ';</script>';
    echo '<noscript><p>JavaScript is off. Use the button above.</p></noscript>';
    echo '</div>';
}

add_action( 'wp_head', 'sqs_app_bar_styles' );
function sqs_app_bar_styles() { ?>
<style>
/* ── Quote Builder — Shared App Bar, Toolbar & Buttons ── */
.sqs-app-bar{display:flex;align-items:center;justify-content:space-between;background:#ffffff;padding:0 22px;height:58px;border-bottom:1px solid #d7dde8;position:sticky;top:0;z-index:9999;box-shadow:0 1px 8px rgba(15,23,42,.06);gap:14px;box-sizing:border-box;width:100%;font-family:'DM Sans',Arial,Helvetica,sans-serif;}
.sqs-app-bar-left{display:flex;align-items:center;gap:11px;flex-shrink:1;min-width:0;}
.sqs-app-bar-logo{height:31px;width:auto;display:block;}
.sqs-app-bar-divider{width:1px;height:24px;background:#dbe2ec;flex-shrink:0;}
.sqs-app-bar-name{font-size:12px;font-weight:900;color:#0f1623;text-transform:uppercase;letter-spacing:.16em;white-space:nowrap;line-height:1;}
.sqs-app-bar-product{font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.12em;white-space:nowrap;line-height:1;}
.sqs-app-bar-right{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-shrink:0;}
.sqs-quote-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#f8fafc;border-bottom:1px solid #d7dde8;padding:10px 22px;box-sizing:border-box;width:100%;font-family:'DM Sans',Arial,Helvetica,sans-serif;margin-bottom:18px;position:sticky;top:58px;z-index:9998;}
.sqs-bar-btn{display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:5px !important;height:36px !important;padding:0 14px !important;border-radius:8px !important;font-size:12px !important;font-weight:800 !important;cursor:pointer !important;border:1px solid #cbd5e1 !important;text-decoration:none !important;white-space:nowrap !important;transition:background .15s,border-color .15s,color .15s !important;line-height:1 !important;font-family:inherit !important;background:#ffffff !important;color:#334155 !important;box-shadow:none !important;box-sizing:border-box !important;vertical-align:middle !important;}
.sqs-bar-btn:hover{background:#f1f5f9 !important;border-color:#94a3b8 !important;color:#0f1623 !important;}
.sqs-bar-btn-primary{background:#c62828 !important;border-color:#b91c1c !important;color:#ffffff !important;}
.sqs-bar-btn-primary:hover{background:#b91c1c !important;border-color:#991b1b !important;color:#ffffff !important;}
.sqs-bar-sep{width:1px;height:24px;background:#dbe2ec;flex-shrink:0;}
.sqs-toolbar-spacer{flex:1 1 auto;min-width:12px;}
.sqs-status-control{display:inline-flex;align-items:center;gap:8px;background:#ffffff;border:1px solid #d7dde8;border-radius:8px;padding:0 8px 0 12px;height:36px;box-sizing:border-box;vertical-align:middle;white-space:nowrap;}
.sqs-status-label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#64748b;line-height:1;flex-shrink:0;}
.sqs-bar-select{display:block;height:28px;width:112px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#0f172a;font-size:12px;font-weight:800;padding:0 6px 0 8px;font-family:inherit;outline:none;box-sizing:border-box;margin:0;align-self:center;appearance:auto;-webkit-appearance:menulist;}
.sqs-bar-status{display:inline-flex;align-items:center;height:36px;font-size:11px;font-weight:800;color:#64748b;background:transparent;border:0;padding:0 4px;white-space:nowrap;line-height:1;}
.sqs-load-modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.46);z-index:10000;align-items:center;justify-content:center;padding:18px;}
.sqs-load-panel{background:#fff;width:min(760px,96vw);max-height:82vh;overflow:auto;border-radius:18px;box-shadow:0 20px 60px rgba(15,23,42,.3);border:1px solid #dbe2ec;}
.sqs-load-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #e2e8f0;}
.sqs-load-head h2{margin:0;font-size:18px;color:#0f172a;}
.sqs-load-close{border:1px solid #cbd5e1;background:#fff;color:#334155;border-radius:999px;padding:7px 10px;font-weight:800;cursor:pointer;}
.sqs-load-body{padding:14px 18px 18px;}
.sqs-load-table{width:100%;border-collapse:collapse;font-size:13px;}
.sqs-load-table th{background:#f8fafc;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;}
.sqs-load-table th,.sqs-load-table td{border-bottom:1px solid #e2e8f0;padding:9px 8px;vertical-align:middle;}
.sqs-load-open{border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:999px;padding:7px 11px;font-size:12px;font-weight:800;cursor:pointer;}
@media(max-width:820px){.sqs-app-bar{padding:10px 14px;height:auto;min-height:58px;flex-wrap:wrap;}.sqs-app-bar-right{flex-shrink:1;justify-content:flex-start;}.sqs-quote-toolbar{padding:10px 14px;margin-bottom:14px;}.sqs-toolbar-spacer,.sqs-bar-sep{display:none;}}
</style><?php }

function sqs_pricing_calculator_reset_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $msg = '';
    if ( isset($_POST['sqs_do_reset']) && check_admin_referer('sqs_reset_credentials') ) {
        delete_option('sqs_calc_sales_pass_hash');
        delete_option('sqs_calc_editor_pass_hash');
        update_option('sqs_calc_sales_user','sales_team');
        update_option('sqs_calc_sales_pass_hash', wp_hash_password('ChangeMe123!'));
        update_option('sqs_calc_editor_user','sales_admin');
        update_option('sqs_calc_editor_pass_hash', wp_hash_password('ChangeMe123!'));
        $msg = 'Done! Sales: sales_team / ChangeMe123!  |  Editor: sales_admin / ChangeMe123!';
    }
    $eu = get_option('sqs_calc_editor_user','NOT SET');
    $eh = get_option('sqs_calc_editor_pass_hash','');
    $test = $eh ? wp_check_password('ChangeMe123!',$eh) : false;
    ?>
    <div class="wrap"><h1>Reset Logins</h1>
    <?php if($msg):?><div class="notice notice-success"><p><strong><?php echo esc_html($msg);?></strong></p></div><?php endif;?>
    <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;max-width:500px;margin:16px 0;">
        <h2 style="margin-top:0;">Current Status</h2>
        <p>Editor username: <code><?php echo esc_html($eu);?></code></p>
        <p>Editor hash exists: <?php echo $eh ? '<strong style="color:green;">Yes</strong>' : '<strong style="color:red;">No</strong>'; ?></p>
        <p>ChangeMe123! check: <?php echo $test ? '<strong style="color:green;">PASS</strong>' : '<strong style="color:red;">FAIL</strong>'; ?></p>
    </div>
    <div style="background:#fff;border:2px solid #c62828;border-radius:12px;padding:24px;max-width:500px;">
        <h2 style="margin-top:0;color:#c62828;">Reset All Logins</h2>
        <p>Resets both logins to: <code>ChangeMe123!</code></p>
        <form method="post"><?php wp_nonce_field('sqs_reset_credentials');?>
            <input type="hidden" name="sqs_do_reset" value="1"/>
            <button class="button button-primary">Reset Now</button>
        </form>
    </div></div>
    <?php
}

add_shortcode( 'sqs_portal', 'sqs_pricing_calculator_portal_shortcode' );
function sqs_pricing_calculator_portal_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'sales_url' => '', 'admin_url' => '' ), $atts );
    $company   = get_option( 'sqs_company_name', 'Quote Builder' );
    $logo      = get_option( 'sqs_logo_url', '' );
    $sales_url = $atts['sales_url'] ?: home_url( '/sales-quoting-system/' );
    $admin_url = $atts['admin_url'] ?: home_url( '/front-end-access/' );
    ob_start(); ?>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet"/>
    <style>.sqs-portal-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f1623 0%,#1a2236 50%,#0f1623 100%);font-family:'DM Sans',sans-serif;padding:24px}.sqs-portal-card{background:#fff;border-radius:24px;overflow:hidden;width:100%;max-width:440px;box-shadow:0 24px 64px rgba(0,0,0,.4);text-align:center}
.sqs-portal-top{background:#ffffff;padding:36px 44px 28px}
.sqs-portal-bottom{background:linear-gradient(160deg,#e8ecf2 0%,#f0f3f7 40%,#e2e8ef 100%);padding:28px 44px 40px;border-top:1px solid #d0d7e3}.sqs-portal-logo{display:block;max-width:220px;height:auto;margin:0 auto 20px}.sqs-portal-kicker{font-size:10px;font-weight:700;color:#8896a8;text-transform:uppercase;letter-spacing:.18em;margin-bottom:8px;font-family:'DM Mono',monospace}.sqs-portal-title{font-size:26px;font-weight:800;color:#0f1623;margin:0 0 6px;letter-spacing:-.02em}.sqs-portal-sub{font-size:14px;color:#667085;margin:0 0 32px}.sqs-portal-btn{display:flex;align-items:center;gap:14px;width:100%;padding:16px 20px;border-radius:14px;text-decoration:none;margin-bottom:12px;transition:transform .15s,box-shadow .15s;border:2px solid transparent;box-sizing:border-box}.sqs-portal-btn:hover{transform:translateY(-2px)}.sqs-portal-btn:last-of-type{margin-bottom:0}.sqs-portal-btn-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}.sqs-portal-btn-label{font-size:15px;font-weight:800;display:block;line-height:1.2}.sqs-portal-btn-desc{font-size:12px;display:block;margin-top:2px;opacity:.75}.sqs-portal-btn.sales{background:linear-gradient(135deg,#fef2f2,#fee2e2);border-color:#fecaca;color:#7f1d1d;box-shadow:0 4px 16px rgba(198,40,40,.12)}.sqs-portal-btn.sales:hover{box-shadow:0 8px 24px rgba(198,40,40,.20);border-color:#c62828}.sqs-portal-btn.sales .sqs-portal-btn-icon{background:#c62828;color:white}.sqs-portal-btn.admin{background:linear-gradient(135deg,#eff6ff,#dbeafe);border-color:#bfdbfe;color:#1e3a5f;box-shadow:0 4px 16px rgba(2,132,199,.10)}.sqs-portal-btn.admin:hover{box-shadow:0 8px 24px rgba(2,132,199,.18);border-color:#0284c7}.sqs-portal-btn.admin .sqs-portal-btn-icon{background:#0284c7;color:white}.sqs-portal-footer{margin-top:28px;font-size:11px;color:#9ca3af;font-family:'DM Mono',monospace}

</style>
    <div class="sqs-portal-wrap"><div class="sqs-portal-card">
        <div class="sqs-portal-top">
            <?php if($logo):?><img src="<?php echo esc_url($logo);?>" alt="<?php echo esc_attr($company);?>" class="sqs-portal-logo"/><?php endif;?>
            <div class="sqs-portal-kicker" style="margin-bottom:20px;padding-bottom:4px;">Internal Portal</div>
            <h1 class="sqs-portal-title">Quote Builder</h1>
            <p class="sqs-portal-sub">Select your access level to continue</p>
        </div>
        <div class="sqs-portal-bottom">
        <a href="<?php echo esc_url($sales_url);?>" class="sqs-portal-btn sales">
            <div class="sqs-portal-btn-icon">&#128202;</div>
            <div><span class="sqs-portal-btn-label">Quote Builder</span><span class="sqs-portal-btn-desc">Quote pricing for Tubing, BSB &amp; Zipper</span></div>
        </a>
        <a href="<?php echo esc_url($admin_url);?>" class="sqs-portal-btn admin">
            <div class="sqs-portal-btn-icon">&#9881;</div>
            <div><span class="sqs-portal-btn-label">Data Editor</span><span class="sqs-portal-btn-desc">Update resin prices &amp; formula costs</span></div>
        </a>
        <div class="sqs-portal-footer">Quote Builder &mdash; Authorized Access Only</div>
        </div>
    </div></div>
    <?php return ob_get_clean();
}


function sqs_pricing_calculator_sales_quote_builder_url() {
    $page = get_page_by_path( 'sales-quoting-system' );
    return $page ? get_permalink( $page ) : home_url( '/sales-quoting-system/' );
}

function sqs_pricing_calculator_data_editor_url() {
    $page = get_page_by_path( 'front-end-access' );
    return $page ? get_permalink( $page ) : home_url( '/front-end-access/' );
}

function sqs_pricing_calculator_redirect_to_sales_quote_builder() {
    sqs_pricing_calculator_external_admin_link_page( 'Quote Builder', sqs_pricing_calculator_sales_quote_builder_url() );
}

function sqs_pricing_calculator_redirect_to_data_editor() {
    sqs_pricing_calculator_external_admin_link_page( 'Data Editor', sqs_pricing_calculator_data_editor_url() );
}

function sqs_pricing_calculator_frontend_settings_menu() {
    add_submenu_page(
        'sqs-pricing-calculator',
        'Quote Builder',
        'Quote Builder',
        'manage_options',
        'sqs-sales-quote-builder',
        'sqs_pricing_calculator_redirect_to_sales_quote_builder'
    );

    add_submenu_page(
        'sqs-pricing-calculator',
        'Data Editor',
        'Data Editor',
        'manage_options',
        'sqs-data-editor',
        'sqs_pricing_calculator_redirect_to_data_editor'
    );

    add_submenu_page(
        'sqs-pricing-calculator',
        'Login & Branding Settings',
        'Login & Branding',
        'manage_options',
        'sqs-pricing-calculator-access',
        'sqs_pricing_calculator_access_page'
    );

    add_submenu_page(
        'sqs-pricing-calculator',
        'Reset Logins',
        'Reset Logins',
        'manage_options',
        'sqs-pricing-calculator-reset',
        'sqs_pricing_calculator_reset_page'
    );
}
add_action( 'admin_menu', 'sqs_pricing_calculator_frontend_settings_menu' );

function sqs_pricing_calculator_access_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $msg = '';
    if ( isset( $_POST['sqs_access_action'] ) ) {
        check_admin_referer( 'sqs_calc_access_save', 'sqs_calc_access_nonce' );
        if ( isset($_POST['sqs_company_name']) ) update_option('sqs_company_name', sanitize_text_field(wp_unslash($_POST['sqs_company_name'])));
        if ( isset($_POST['sqs_logo_url']) ) update_option('sqs_logo_url', esc_url_raw(wp_unslash($_POST['sqs_logo_url'])));
        if ( isset($_POST['sqs_company_address']) ) update_option('sqs_company_address', sanitize_textarea_field(wp_unslash($_POST['sqs_company_address'])));
        if ( isset($_POST['sqs_quote_terms']) ) update_option('sqs_quote_terms', sanitize_textarea_field(wp_unslash($_POST['sqs_quote_terms'])));
        $su = isset($_POST['sqs_access_user']) ? sanitize_text_field(wp_unslash($_POST['sqs_access_user'])) : '';
        $sp = isset($_POST['sqs_access_pass']) ? (string)wp_unslash($_POST['sqs_access_pass']) : '';
        if ($su) update_option('sqs_calc_sales_user',$su);
        if ($sp) update_option('sqs_calc_sales_pass_hash', wp_hash_password($sp));
        $eu = isset($_POST['sqs_editor_user']) ? sanitize_text_field(wp_unslash($_POST['sqs_editor_user'])) : '';
        $ep = isset($_POST['sqs_editor_pass']) ? (string)wp_unslash($_POST['sqs_editor_pass']) : '';
        if ($eu) update_option('sqs_calc_editor_user',$eu);
        if ($ep) update_option('sqs_calc_editor_pass_hash', wp_hash_password($ep));
        $msg = 'Settings saved.';
    }
    $su = get_option('sqs_calc_sales_user','sales_team');
    $eu = get_option('sqs_calc_editor_user','sales_admin');
    $co = get_option('sqs_company_name','');
    $lg = get_option('sqs_logo_url','');
    $ca = get_option('sqs_company_address','');
    $qt = get_option('sqs_quote_terms','');
    ?>
    <div class="wrap">
    <h1>Quote Builder &mdash; Settings</h1>
    <?php if($msg):?><div class="notice notice-success is-dismissible"><p><?php echo esc_html($msg);?></p></div><?php endif;?>
    <form method="post" style="max-width:580px;">
        <?php wp_nonce_field('sqs_calc_access_save','sqs_calc_access_nonce');?>
        <input type="hidden" name="sqs_access_action" value="save"/>
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:24px;margin:16px 0;">
            <h2 style="margin-top:0;border-bottom:1px solid #eee;padding-bottom:10px;">&#127970; Branding</h2>
            <p><label><strong>Company Name</strong><br><input type="text" name="sqs_company_name" value="<?php echo esc_attr($co);?>" class="regular-text" placeholder="e.g. Fruth Custom Packaging" style="margin-top:4px;"></label></p>
            <p><label><strong>Logo URL</strong><br><input type="url" name="sqs_logo_url" value="<?php echo esc_attr($lg);?>" class="large-text" placeholder="https://..." style="margin-top:4px;"></label><br><span class="description">Upload via Media Library then paste URL here.</span></p>
            <p><label><strong>Company Address</strong> <span class="description">(shown on printed quotes)</span><br><textarea name="sqs_company_address" class="large-text" rows="2" placeholder="123 Main St&#10;City, ST 00000" style="margin-top:4px;"><?php echo esc_textarea($ca);?></textarea></label></p>
            <p><label><strong>Quote Terms / Disclaimer</strong> <span class="description">(printed at bottom of every quote)</span><br><textarea name="sqs_quote_terms" class="large-text" rows="3" placeholder="Quotes are valid for 30 days. All orders are subject to standard terms and conditions." style="margin-top:4px;"><?php echo esc_textarea($qt);?></textarea></label></p>
        </div>
        <div style="background:#fff;border:2px solid #c62828;border-radius:12px;padding:24px;margin-bottom:16px;">
            <h2 style="margin-top:0;color:#c62828;border-bottom:1px solid #fecaca;padding-bottom:10px;">&#128100; Sales Login <code style="font-size:12px;font-weight:400;">[sqs_pricing_calculator]</code></h2>
            <p><label><strong>Username</strong><br><input type="text" name="sqs_access_user" value="<?php echo esc_attr($su);?>" class="regular-text" style="margin-top:4px;"></label></p>
            <p><label><strong>New Password</strong><br><input type="password" name="sqs_access_pass" value="" class="regular-text" autocomplete="new-password" style="margin-top:4px;"></label><br><span class="description">Leave blank to keep current.</span></p>
        </div>
        <div style="background:#fff;border:2px solid #0284c7;border-radius:12px;padding:24px;margin-bottom:24px;">
            <h2 style="margin-top:0;color:#0284c7;border-bottom:1px solid #bae6fd;padding-bottom:10px;">&#9881; Data Editor Login <code style="font-size:12px;font-weight:400;">[sqs_pricing_data_admin]</code></h2>
            <p><label><strong>Username</strong><br><input type="text" name="sqs_editor_user" value="<?php echo esc_attr($eu);?>" class="regular-text" style="margin-top:4px;"></label></p>
            <p><label><strong>New Password</strong><br><input type="password" name="sqs_editor_pass" value="" class="regular-text" autocomplete="new-password" style="margin-top:4px;"></label><br><span class="description">Leave blank to keep current.</span></p>
        </div>
        <p><button class="button button-primary button-large">Save Settings</button></p>
    </form>
    </div>
    <?php
}

function sqs_pricing_calculator_admin_notes( $key ) {
    $notes = array(
        'packaging'             => 'Packaging fees used by the calculator.',
        'resinDensity'          => 'Density values by resin type.',
        'formulaCosts'          => 'Formula cost per pound. These values usually change when material costs change.',
        'formulaOptions'        => 'Dropdown list of available formulas.',
        'filmTypes'             => 'Dropdown list of available film types.',
        'resinTypes'            => 'Dropdown list of available resin types.',
        'setupWidthBuckets'     => 'Width breakpoints used by setup tables.',
        'prodWidthBuckets'      => 'Width breakpoints used by production rate tables.',
        'zipperWidthBuckets'    => 'Width breakpoints used by zipper production rates.',
        'setupMultiplier'       => 'Setup multiplier by film type.',
        'setupHours'            => 'Setup hours by film type.',
        'setupLbsTable'         => 'Setup pounds by film type and setup width bucket.',
        'setupOpsExtrusion'     => 'Extrusion setup operators by setup width bucket.',
        'inlineAdditionalOps'   => 'Additional inline operators by setup width bucket.',
        'minimumSetupFees'      => 'Minimum setup fee by setup width bucket.',
        'extrusionRateTable'    => 'Extrusion production rate by gauge and production width bucket.',
        'inlineRateTable'       => 'Inline production rate by gauge and production width bucket.',
        'zipperQtyPerHour'      => 'Zipper quantity per hour by zipper width bucket.',
        'laborRates'            => 'Hourly labor rates used in calculator math.',
        'defaults'              => 'Default starting values shown on calculator load.',
    );
    return isset( $notes[ $key ] ) ? $notes[ $key ] : 'Calculator data table.';
}

function sqs_pricing_calculator_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table = sqs_pricing_calculator_table_name();
    sqs_pricing_calculator_activate();

    $message = '';
    $error = '';

    if ( isset( $_POST['sqs_calc_action'] ) ) {
        check_admin_referer( 'sqs_calc_admin_save', 'sqs_calc_nonce' );
        $action = sanitize_text_field( wp_unslash( $_POST['sqs_calc_action'] ) );

        if ( 'save_all' === $action && isset( $_POST['sqs_data'] ) && is_array( $_POST['sqs_data'] ) ) {
            foreach ( $_POST['sqs_data'] as $key => $json ) {
                $key = sqs_pricing_calculator_clean_data_key( $key );
                $json = wp_unslash( $json );
                $decoded = json_decode( $json, true );
                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    $error = 'One or more tables were not saved because the data could not be read: ' . esc_html( json_last_error_msg() );
                    break;
                }
                $wpdb->update(
                    $table,
                    array(
                        'data_json'  => wp_json_encode( $decoded, JSON_PRETTY_PRINT ),
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( 'data_key' => $key )
                );
            }
            if ( ! $error ) {
                $message = 'Calculator tables saved.';
            }
        } elseif ( 'reset_defaults' === $action ) {
            sqs_pricing_calculator_seed_defaults( true );
            $message = 'Calculator data reset to the original defaults.';
        } elseif ( 'import_json' === $action && ! empty( $_POST['sqs_import_json'] ) ) {
            $import = json_decode( wp_unslash( $_POST['sqs_import_json'] ), true );
            if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $import ) ) {
                $error = 'Import failed. The pasted data is not valid calculator JSON.';
            } else {
                $labels = sqs_pricing_calculator_labels();
                foreach ( $import as $key => $value ) {
                    $safe_key = sqs_pricing_calculator_clean_data_key( $key );
                    $row = array(
                        'data_key'   => $safe_key,
                        'data_label' => isset( $labels[ $safe_key ] ) ? $labels[ $safe_key ] : $safe_key,
                        'data_json'  => wp_json_encode( $value, JSON_PRETTY_PRINT ),
                        'updated_at' => current_time( 'mysql' ),
                    );
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE data_key = %s", $safe_key ) );
                    if ( $exists ) {
                        $wpdb->update( $table, $row, array( 'data_key' => $safe_key ) );
                    } else {
                        $wpdb->insert( $table, $row );
                    }
                }
                $message = 'Calculator JSON imported.';
            }
        }
    }

    $rows = $wpdb->get_results( "SELECT data_key, data_label, data_json, updated_at FROM $table ORDER BY id ASC", ARRAY_A );
    $all_data = sqs_pricing_calculator_get_data();
    ?>
    <div class="wrap sqs-admin-wrap">
        <h1>Pricing Tables</h1>
        <p>Use these tables to update calculator rates, dropdowns, defaults, and lookup values. The public calculator still uses <code>[sqs_pricing_calculator]</code>.</p>
        <?php if ( $message ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div><?php endif; ?>
        <?php if ( $error ) : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

        <style>
            .sqs-admin-actions { position:sticky; top:32px; z-index:30; background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:12px 16px; margin:14px 0 18px; box-shadow:0 4px 14px rgba(0,0,0,.06); display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
            .sqs-admin-grid { display:grid; grid-template-columns: 1fr; gap:18px; max-width:1440px; }
            .sqs-admin-card { background:#fff; border:1px solid #dcdcde; border-radius:12px; padding:18px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
            .sqs-admin-card h2 { margin:0 0 4px; font-size:20px; }
            .sqs-small { color:#646970; font-size:12px; }
            .sqs-editor-table-wrap { overflow:auto; border:1px solid #dcdcde; border-radius:8px; margin-top:12px; }
            .sqs-editor-table { width:100%; border-collapse:collapse; min-width:520px; }
            .sqs-editor-table th { background:#f6f7f7; font-weight:700; position:sticky; top:0; z-index:1; }
            .sqs-editor-table th,.sqs-editor-table td { border-bottom:1px solid #eee; border-right:1px solid #eee; padding:7px; text-align:left; vertical-align:middle; }
            .sqs-editor-table td:last-child,.sqs-editor-table th:last-child { border-right:0; }
            .sqs-editor-table input,.sqs-editor-table select { width:100%; max-width:100%; min-height:32px; }
            .sqs-editor-table .sqs-row-label { min-width:180px; font-weight:600; background:#fbfbfc; }
            .sqs-card-tools { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }
            .sqs-hidden-json { display:none; width:100%; min-height:170px; font-family:Consolas, Monaco, monospace; font-size:12px; }
            .sqs-export { width:100%; min-height:260px; font-family:Consolas, Monaco, monospace; font-size:12px; }
            .sqs-tabs { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0 4px; }
            .sqs-tabs a { text-decoration:none; border:1px solid #c3c4c7; background:#fff; border-radius:999px; padding:6px 12px; color:#1d2327; }
            .sqs-tabs a:hover { background:#f0f0f1; }
            .sqs-danger { color:#b32d2e; }
        </style>

        <div class="sqs-tabs">
            <?php foreach ( $rows as $nav_row ) : ?>
                <a href="#sqs-table-<?php echo esc_attr( $nav_row['data_key'] ); ?>"><?php echo esc_html( $nav_row['data_label'] ); ?></a>
            <?php endforeach; ?>
        </div>

        <form method="post" id="sqs-admin-form">
            <?php wp_nonce_field( 'sqs_calc_admin_save', 'sqs_calc_nonce' ); ?>
            <input type="hidden" name="sqs_calc_action" value="save_all" />
            <div class="sqs-admin-actions">
                <button class="button button-primary button-large">Save All Tables</button>
                <span class="sqs-small">Edit cells like a spreadsheet, then save. Use Advanced JSON only for backup-level changes.</span>
            </div>

            <div class="sqs-admin-grid">
                <?php foreach ( $rows as $row ) :
                    $decoded = json_decode( $row['data_json'], true );
                    $json_for_attr = esc_attr( wp_json_encode( $decoded ) );
                    ?>
                    <div class="sqs-admin-card sqs-editor-card" id="sqs-table-<?php echo esc_attr( $row['data_key'] ); ?>" data-key="<?php echo esc_attr( $row['data_key'] ); ?>">
                        <h2><?php echo esc_html( $row['data_label'] ); ?></h2>
                        <p class="sqs-small"><?php echo esc_html( sqs_pricing_calculator_admin_notes( $row['data_key'] ) ); ?> Last updated: <?php echo esc_html( $row['updated_at'] ); ?></p>
                        <input type="hidden" class="sqs-data-json" name="sqs_data[<?php echo esc_attr( $row['data_key'] ); ?>]" value="<?php echo esc_attr( wp_json_encode( $decoded ) ); ?>" />
                        <div class="sqs-editor" data-key="<?php echo esc_attr( $row['data_key'] ); ?>" data-value="<?php echo $json_for_attr; ?>"></div>
                        <div class="sqs-card-tools">
                            <button type="button" class="button sqs-add-row">Add Row</button>
                            <button type="button" class="button sqs-show-json">Advanced JSON</button>
                        </div>
                        <textarea class="sqs-hidden-json"><?php echo esc_textarea( wp_json_encode( $decoded, JSON_PRETTY_PRINT ) ); ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="sqs-admin-actions">
                <button class="button button-primary button-large">Save All Tables</button>
                <span class="sqs-small">Quote Builder</span>
            </div>
        </form>

        <div class="sqs-admin-card" style="max-width:1440px;margin-top:18px;">
            <h2>Backup / Import</h2>
            <p class="sqs-small">Copy this full calculator data before major changes. Import only when replacing the full data set.</p>
            <textarea class="sqs-export" readonly><?php echo esc_textarea( wp_json_encode( $all_data, JSON_PRETTY_PRINT ) ); ?></textarea>
            <form method="post" style="margin-top:14px;">
                <?php wp_nonce_field( 'sqs_calc_admin_save', 'sqs_calc_nonce' ); ?>
                <input type="hidden" name="sqs_calc_action" value="import_json" />
                <h3>Import Full JSON</h3>
                <textarea name="sqs_import_json" class="sqs-export" placeholder="Paste full calculator JSON here"></textarea>
                <p><button class="button">Import JSON</button></p>
            </form>
            <form method="post" onsubmit="return confirm('Reset all calculator data to the original defaults?');">
                <?php wp_nonce_field( 'sqs_calc_admin_save', 'sqs_calc_nonce' ); ?>
                <input type="hidden" name="sqs_calc_action" value="reset_defaults" />
                <button class="button button-secondary sqs-danger">Reset to Original Defaults</button>
            </form>
        </div>

        <script>
        (function(){
            const data = <?php echo wp_json_encode( $all_data ); ?>;
            const matrixHeaders = {
                setupLbsTable: data.setupWidthBuckets || [],
                extrusionRateTable: data.prodWidthBuckets || [],
                inlineRateTable: data.prodWidthBuckets || []
            };
            const listHeaders = {
                setupOpsExtrusion: data.setupWidthBuckets || [],
                inlineAdditionalOps: data.setupWidthBuckets || [],
                minimumSetupFees: data.setupWidthBuckets || [],
                zipperQtyPerHour: data.zipperWidthBuckets || []
            };
            const numericKeys = new Set(['packaging','resinDensity','formulaCosts','setupMultiplier','setupHours','laborRates']);

            function parseValue(v){
                if (typeof v !== 'string') return v;
                const t = v.trim();
                if (t === '') return '';
                if (/^-?\d+(\.\d+)?$/.test(t)) return Number(t);
                return t;
            }
            function esc(s){ return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
            function input(v, cls){ return `<input type="text" class="${cls||'sqs-cell'}" value="${esc(v)}">`; }
            function renderMap(key, value){
                let rows = Object.entries(value || {}).map(([k,v]) => `<tr><td>${input(k,'sqs-map-key')}</td><td>${input(v,'sqs-map-value')}</td><td><button type="button" class="button-link-delete sqs-delete-row">Remove</button></td></tr>`).join('');
                return `<div class="sqs-editor-table-wrap"><table class="sqs-editor-table" data-kind="map"><thead><tr><th>Name</th><th>Value</th><th></th></tr></thead><tbody>${rows}</tbody></table></div>`;
            }
            function renderList(key, value){
                const headers = listHeaders[key];
                if (headers && headers.length === (value || []).length) {
                    let cells = value.map((v,i)=>`<td><label class="screen-reader-text">${esc(headers[i])}</label>${input(v,'sqs-list-value')}</td>`).join('');
                    let head = headers.map(h=>`<th>${esc(h)}</th>`).join('');
                    return `<div class="sqs-editor-table-wrap"><table class="sqs-editor-table" data-kind="list"><thead><tr>${head}</tr></thead><tbody><tr>${cells}</tr></tbody></table></div>`;
                }
                let rows = (value || []).map(v => `<tr><td>${input(v,'sqs-list-value')}</td><td><button type="button" class="button-link-delete sqs-delete-row">Remove</button></td></tr>`).join('');
                return `<div class="sqs-editor-table-wrap"><table class="sqs-editor-table" data-kind="list"><thead><tr><th>Value</th><th></th></tr></thead><tbody>${rows}</tbody></table></div>`;
            }
            function renderMatrix(key, value){
                const headers = matrixHeaders[key] || [];
                let head = `<th>Row</th>` + headers.map(h=>`<th>${esc(h)}</th>`).join('') + `<th></th>`;
                let rows = Object.entries(value || {}).map(([rowName, arr]) => {
                    const cells = headers.map((h,i)=>`<td>${input((arr||[])[i] ?? '', 'sqs-matrix-value')}</td>`).join('');
                    return `<tr><td class="sqs-row-label">${input(rowName,'sqs-matrix-key')}</td>${cells}<td><button type="button" class="button-link-delete sqs-delete-row">Remove</button></td></tr>`;
                }).join('');
                return `<div class="sqs-editor-table-wrap"><table class="sqs-editor-table" data-kind="matrix" data-headers='${esc(JSON.stringify(headers))}'><thead><tr>${head}</tr></thead><tbody>${rows}</tbody></table></div>`;
            }
            function renderDefaults(value){
                let html = '';
                Object.entries(value || {}).forEach(([section, fields]) => {
                    html += `<h3>${esc(section.charAt(0).toUpperCase()+section.slice(1))} Defaults</h3>`;
                    html += `<div class="sqs-editor-table-wrap"><table class="sqs-editor-table" data-kind="defaults" data-section="${esc(section)}"><thead><tr><th>Field</th><th>Default Value</th></tr></thead><tbody>`;
                    Object.entries(fields || {}).forEach(([k,v]) => { html += `<tr><td>${input(k,'sqs-default-key')}</td><td>${input(v,'sqs-default-value')}</td></tr>`; });
                    html += `</tbody></table></div>`;
                });
                return html;
            }
            function renderEditor(el){
                const key = el.dataset.key;
                const value = JSON.parse(el.dataset.value || 'null');
                let html = '';
                if (key === 'defaults') html = renderDefaults(value);
                else if (matrixHeaders[key]) html = renderMatrix(key, value);
                else if (Array.isArray(value)) html = renderList(key, value);
                else if (value && typeof value === 'object') html = renderMap(key, value);
                else html = `<p>Unsupported value. Use Advanced JSON.</p>`;
                el.innerHTML = html;
            }
            function serializeCard(card){
                const key = card.dataset.key;
                const editor = card.querySelector('.sqs-editor');
                let out;
                if (card.querySelector('.sqs-hidden-json').style.display === 'block') {
                    try { out = JSON.parse(card.querySelector('.sqs-hidden-json').value); } catch(e) { alert('Advanced JSON has an error in '+key+': '+e.message); throw e; }
                } else if (key === 'defaults') {
                    out = {};
                    editor.querySelectorAll('table[data-kind="defaults"]').forEach(tbl => {
                        const section = tbl.dataset.section;
                        out[section] = {};
                        tbl.querySelectorAll('tbody tr').forEach(tr => {
                            const k = tr.querySelector('.sqs-default-key').value.trim();
                            if (k) out[section][k] = parseValue(tr.querySelector('.sqs-default-value').value);
                        });
                    });
                } else if (matrixHeaders[key]) {
                    out = {};
                    editor.querySelectorAll('tbody tr').forEach(tr => {
                        const k = tr.querySelector('.sqs-matrix-key').value.trim();
                        if (k) out[k] = Array.from(tr.querySelectorAll('.sqs-matrix-value')).map(i=>parseValue(i.value));
                    });
                } else if (Array.isArray(JSON.parse(editor.dataset.value || '[]'))) {
                    out = Array.from(editor.querySelectorAll('.sqs-list-value')).map(i=>parseValue(i.value)).filter(v=>v!=='' );
                } else {
                    out = {};
                    editor.querySelectorAll('tbody tr').forEach(tr => {
                        const kEl = tr.querySelector('.sqs-map-key');
                        const vEl = tr.querySelector('.sqs-map-value');
                        if (kEl && vEl && kEl.value.trim()) out[kEl.value.trim()] = parseValue(vEl.value);
                    });
                }
                const pretty = JSON.stringify(out, null, 2);
                card.querySelector('.sqs-data-json').value = JSON.stringify(out);
                card.querySelector('.sqs-hidden-json').value = pretty;
            }
            document.querySelectorAll('.sqs-editor').forEach(renderEditor);
            document.querySelectorAll('.sqs-add-row').forEach(btn => btn.addEventListener('click', function(){
                const card = btn.closest('.sqs-editor-card');
                const key = card.dataset.key;
                const table = card.querySelector('.sqs-editor-table');
                if (!table || key === 'defaults') { alert('Rows are fixed for this table. Edit the existing cells.'); return; }
                const kind = table.dataset.kind;
                if (kind === 'map') table.querySelector('tbody').insertAdjacentHTML('beforeend', `<tr><td>${input('','sqs-map-key')}</td><td>${input('','sqs-map-value')}</td><td><button type="button" class="button-link-delete sqs-delete-row">Remove</button></td></tr>`);
                if (kind === 'list') table.querySelector('tbody').insertAdjacentHTML('beforeend', `<tr><td>${input('','sqs-list-value')}</td><td><button type="button" class="button-link-delete sqs-delete-row">Remove</button></td></tr>`);
                if (kind === 'matrix') {
                    const headers = JSON.parse(table.dataset.headers || '[]');
                    table.querySelector('tbody').insertAdjacentHTML('beforeend', `<tr><td class="sqs-row-label">${input('','sqs-matrix-key')}</td>${headers.map(()=>`<td>${input('','sqs-matrix-value')}</td>`).join('')}<td><button type="button" class="button-link-delete sqs-delete-row">Remove</button></td></tr>`);
                }
            }));
            document.addEventListener('click', function(e){
                if (e.target.classList.contains('sqs-delete-row')) e.target.closest('tr').remove();
                if (e.target.classList.contains('sqs-show-json')) {
                    const card = e.target.closest('.sqs-editor-card');
                    try { serializeCard(card); } catch(err) { return; }
                    const ta = card.querySelector('.sqs-hidden-json');
                    ta.style.display = ta.style.display === 'block' ? 'none' : 'block';
                    e.target.textContent = ta.style.display === 'block' ? 'Hide Advanced JSON' : 'Advanced JSON';
                }
            });
            document.getElementById('sqs-admin-form').addEventListener('submit', function(e){
                try { document.querySelectorAll('.sqs-editor-card').forEach(serializeCard); }
                catch(err) { e.preventDefault(); }
            });
        })();
        </script>
    </div>
    <?php
}


// ─── Private Frontend Data Editor ─────────────────────────────────────────────

add_action( 'init', 'sqs_pricing_calculator_maybe_start_session', 1 );
function sqs_pricing_calculator_maybe_start_session() {
    if ( ! session_id() && ! headers_sent() ) {
        session_start();
    }
}

add_action( 'wp_ajax_sqs_signout',        'sqs_pricing_calculator_ajax_signout' );
add_action( 'wp_ajax_nopriv_sqs_signout', 'sqs_pricing_calculator_ajax_signout' );
function sqs_pricing_calculator_ajax_signout() {
    sqs_pricing_calculator_maybe_start_session();
    unset( $_SESSION['sqs_calc_sales_logged_in'] );
    unset( $_SESSION['sqs_calc_frontend_logged_in'] );
    wp_send_json_success( array( 'redirect' => sqs_pricing_calculator_portal_url() ) );
}

function sqs_pricing_calculator_signout_url() {
    return sqs_pricing_calculator_portal_url();
}

function sqs_pricing_calculator_frontend_is_logged_in() {
    return ! empty( $_SESSION['sqs_calc_frontend_logged_in'] ) && true === $_SESSION['sqs_calc_frontend_logged_in'];
}

function sqs_pricing_calculator_frontend_login_message() {
    return isset( $_SESSION['sqs_calc_frontend_message'] ) ? sanitize_text_field( $_SESSION['sqs_calc_frontend_message'] ) : '';
}

function sqs_pricing_calculator_frontend_set_message( $message ) {
    $_SESSION['sqs_calc_frontend_message'] = sanitize_text_field( $message );
}

function sqs_pricing_calculator_frontend_clear_message() {
    unset( $_SESSION['sqs_calc_frontend_message'] );
}

add_shortcode( 'sqs_pricing_data_admin', 'sqs_pricing_calculator_frontend_editor_shortcode' );

function sqs_pricing_calculator_frontend_editor_shortcode() {
    sqs_pricing_calculator_activate();

    if ( isset( $_POST['sqs_frontend_action'] ) ) {
        $action = sanitize_text_field( wp_unslash( $_POST['sqs_frontend_action'] ) );
        if ( 'login' === $action ) {
            $posted_user = isset( $_POST['sqs_frontend_user'] ) ? sanitize_text_field( wp_unslash( $_POST['sqs_frontend_user'] ) ) : '';
            $posted_pass = isset( $_POST['sqs_frontend_pass'] ) ? (string) wp_unslash( $_POST['sqs_frontend_pass'] ) : '';
            $saved_user  = get_option( 'sqs_calc_editor_user', 'sales_admin' );
            $saved_hash  = get_option( 'sqs_calc_editor_pass_hash', '' );
            if ( hash_equals( (string) $saved_user, (string) $posted_user ) && $saved_hash && wp_check_password( $posted_pass, $saved_hash ) ) {
                $_SESSION['sqs_calc_frontend_logged_in'] = true;
                sqs_pricing_calculator_frontend_clear_message();
            } else {
                sqs_pricing_calculator_frontend_set_message( 'Login failed. Please check the username and password.' );
            }
        } elseif ( 'logout' === $action ) {
            unset( $_SESSION['sqs_calc_frontend_logged_in'] );
            wp_safe_redirect( sqs_pricing_calculator_logout_url() );
            exit;
        } elseif ( 'save_all' === $action && sqs_pricing_calculator_frontend_is_logged_in() ) {
            if ( ! isset( $_POST['sqs_frontend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sqs_frontend_nonce'] ) ), 'sqs_frontend_save' ) ) {
                sqs_pricing_calculator_frontend_set_message( 'Save failed because the page security check expired. Refresh and try again.' );
            } else {
                global $wpdb;
                $table = sqs_pricing_calculator_table_name();
                $error = '';
                if ( isset( $_POST['sqs_data'] ) && is_array( $_POST['sqs_data'] ) ) {
                    foreach ( $_POST['sqs_data'] as $key => $json ) {
                        $key = sqs_pricing_calculator_clean_data_key( $key );
                        $json = wp_unslash( $json );
                        $decoded = json_decode( $json, true );
                        if ( json_last_error() !== JSON_ERROR_NONE ) {
                            $error = 'One or more tables were not saved because a value could not be read.';
                            break;
                        }
                        $wpdb->update(
                            $table,
                            array(
                                'data_json'  => wp_json_encode( $decoded, JSON_PRETTY_PRINT ),
                                'updated_at' => current_time( 'mysql' ),
                            ),
                            array( 'data_key' => $key )
                        );
                    }
                }
                sqs_pricing_calculator_frontend_set_message( $error ? $error : 'Tables saved.' );
            }
        }
    }

    ob_start();
    $message = sqs_pricing_calculator_frontend_login_message();
    sqs_pricing_calculator_frontend_clear_message();
    if ( ! sqs_pricing_calculator_frontend_is_logged_in() ) {
        sqs_pricing_calculator_render_frontend_login( $message );
    } else {
        sqs_pricing_calculator_render_frontend_editor( $message );
    }
    return ob_get_clean();
}

function sqs_pricing_calculator_frontend_styles() {
    ?>
    <style>
        .sqs-data-app{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;border:1px solid #dfe5ef;border-radius:18px;padding:20px;color:#172033;box-shadow:0 18px 40px rgba(15,23,42,.08)}
        .sqs-data-app *{box-sizing:border-box}.sqs-data-top{display:none;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:linear-gradient(135deg,#1f2937,#334155);color:#fff;border-radius:16px;padding:20px;margin-bottom:16px}.sqs-data-top h1{margin:0;font-size:26px;line-height:1.1;color:#fff}.sqs-data-top p{margin:6px 0 0;color:#dbe3ef}.sqs-data-brand{font-size:12px;text-transform:uppercase;letter-spacing:.14em;color:#cbd5e1}.sqs-data-message{background:#ecfdf5;border:1px solid #bbf7d0;color:#14532d;border-radius:12px;padding:10px 12px;margin:12px 0}.sqs-data-login{max-width:460px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;box-shadow:0 18px 40px rgba(15,23,42,.08)}.sqs-data-login h2{margin:0 0 8px;font-size:24px}.sqs-data-field{margin:14px 0}.sqs-data-field label{display:block;font-weight:700;margin-bottom:6px}.sqs-data-field input{width:100%;height:44px;border:1px solid #cbd5e1;border-radius:10px;padding:8px 10px;font-size:16px}.sqs-data-actions{position:sticky;top:10px;z-index:20;display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:12px;margin:12px 0 18px;box-shadow:0 8px 24px rgba(15,23,42,.07)}.sqs-data-btn{appearance:none;border:0;border-radius:999px;background:#c62828;color:#fff;font-weight:800;padding:11px 18px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}.sqs-data-btn:hover{background:#a81d1d;color:#fff}.sqs-data-btn.secondary{background:#334155}.sqs-data-btn.light{background:#eef2f7;color:#172033}.sqs-data-tabs{display:flex;gap:8px;overflow-x:auto;padding:2px 0 12px;margin-bottom:8px}.sqs-data-tabs a{white-space:nowrap;text-decoration:none;color:#172033;background:#fff;border:1px solid #d7dde8;border-radius:999px;padding:8px 12px;font-size:13px;font-weight:700}.sqs-data-tabs a:hover{background:#172033;color:#fff}.sqs-data-grid{display:grid;gap:16px}.sqs-data-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.05)}.sqs-data-card h2{margin:0;font-size:20px}.sqs-data-note{margin:4px 0 12px;color:#64748b;font-size:13px}.sqs-data-table-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:12px}.sqs-data-table{width:100%;border-collapse:collapse;min-width:620px}.sqs-data-table th{background:#f8fafc;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#475569;position:sticky;top:0;z-index:1}.sqs-data-table th,.sqs-data-table td{border-bottom:1px solid #edf2f7;border-right:1px solid #edf2f7;padding:8px;text-align:left}.sqs-data-table th:last-child,.sqs-data-table td:last-child{border-right:0}.sqs-data-table input{width:100%;min-height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:7px 8px;background:#fff}.sqs-data-table .sqs-row-label{background:#fbfdff;font-weight:700;min-width:180px}.sqs-data-tools{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}.sqs-data-mini{border:1px solid #cbd5e1;background:#fff;border-radius:999px;padding:7px 11px;cursor:pointer;font-weight:700}.sqs-data-remove{border:0;background:transparent;color:#b91c1c;cursor:pointer;font-weight:700}.sqs-hidden-json{display:none;width:100%;min-height:160px;margin-top:10px;font-family:Consolas,monospace;font-size:12px}.sqs-data-foot{margin-top:18px;text-align:center;color:#64748b;font-size:12px}@media(max-width:700px){.sqs-data-app{padding:12px}.sqs-data-top{padding:16px}.sqs-data-top h1{font-size:22px}.sqs-data-actions{position:static}.sqs-data-table{min-width:560px}}

.sqs-data-app{padding:0 !important;border-radius:0 !important;border:1px solid #dfe5ef !important;background:#f5f7fb !important;overflow:hidden !important;}
.sqs-data-app > div[style*="height:12px"]{display:none !important;}
.sqs-data-message{display:none !important;}
.sqs-data-tabs{display:flex;gap:8px;overflow-x:auto;padding:12px 22px 8px;margin:0;background:#fff;border-bottom:1px solid #d7dde8;scrollbar-width:thin;}
.sqs-data-tabs a{white-space:nowrap;text-decoration:none;color:#334155;background:#fff;border:1px solid #d7dde8;border-radius:8px;padding:9px 14px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;line-height:1;}
.sqs-data-tabs a:hover{background:#0f1623;border-color:#0f1623;color:#fff;}
.sqs-data-actions{margin:22px 22px 18px !important;}
.sqs-data-grid{padding:0 22px 22px !important;}
@media(max-width:820px){.sqs-data-tabs{padding:10px 14px 8px;}}
    </style>
    <?php
}

function sqs_pricing_calculator_render_frontend_login( $message = '' ) {
    $logo = get_option( 'sqs_logo_url', '' );
    ?>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700;800&display=swap" rel="stylesheet"/>
    <style>
    .sqs-lw{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#edf0f4;font-family:'DM Sans',sans-serif}
    .sqs-lc{background:#fff;border-radius:20px;padding:48px 40px;width:100%;max-width:400px;box-shadow:0 8px 32px rgba(10,15,30,.12);border:1px solid #e2e5eb}
    .sqs-lc img{display:block;max-width:200px;height:auto;margin:0 auto 16px}
    .sqs-lk{font-size:10px;font-weight:700;color:#0284c7;text-transform:uppercase;letter-spacing:.14em;text-align:center;margin-bottom:8px}
    .sqs-lt{font-size:22px;font-weight:800;color:#0f1623;text-align:center;margin:0 0 4px}
    .sqs-ls{font-size:13px;color:#667085;text-align:center;margin:0 0 24px}
    .sqs-lf{margin-bottom:14px}
    .sqs-lf label{display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
    .sqs-lf input{width:100%;border:1px solid #d0d5dd;border-radius:10px;padding:11px 14px;font-size:14px;color:#0f1623;box-sizing:border-box}
    .sqs-lb{width:100%;background:#c62828;color:#fff;border:none;border-radius:12px;padding:14px;font-size:14px;font-weight:700;cursor:pointer;margin-top:6px;letter-spacing:.02em;}
    .sqs-lb:hover{background:#a81d1d;}
    .sqs-le{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px;font-size:13px;color:#c62828;font-weight:600;margin-bottom:14px;text-align:center}
    </style>
    <div class="sqs-lw"><div class="sqs-lc">
        <?php if($logo):?><img src="<?php echo esc_url($logo);?>" alt="Sales Data Edit System"/><?php endif;?>
        <div class="sqs-lk">&#9881; Admin Control Panel</div>
        <h2 class="sqs-lt">Admin Control Panel</h2>
        <p class="sqs-ls">Sign in to update calculator tables</p>
        <?php if($message):?><div class="sqs-le"><?php echo esc_html($message);?></div><?php endif;?>
        <form method="post">
            <input type="hidden" name="sqs_frontend_action" value="login">
            <div class="sqs-lf"><label>Username</label><input type="text" name="sqs_frontend_user" autocomplete="username" required></div>
            <div class="sqs-lf"><label>Password</label><input type="password" name="sqs_frontend_pass" autocomplete="current-password" required></div>
            <button class="sqs-lb" type="submit">Sign In &rarr;</button>
        </form>
        <div style="text-align:center;margin-top:16px;">
            <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:13px 20px;background:#0f1623;color:#ffffff;border:2px solid #0f1623;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;width:100%;box-sizing:border-box;letter-spacing:.02em;">&#8592; Return to Home</a>
        </div>
    </div></div>
    <?php
}

function sqs_pricing_calculator_render_frontend_editor( $message = '' ) {
    global $wpdb;
    $table = sqs_pricing_calculator_table_name();
    $rows = $wpdb->get_results( "SELECT data_key, data_label, data_json, updated_at FROM $table ORDER BY id ASC", ARRAY_A );
    $all_data = sqs_pricing_calculator_get_data();
    sqs_pricing_calculator_frontend_styles();
    ?>
    <div class="sqs-data-app">
        <div class="sqs-app-bar">
            <div class="sqs-app-bar-left">
                <?php $bar_logo = get_option('sqs_logo_url',''); if($bar_logo): ?>
                <img src="<?php echo esc_url($bar_logo); ?>" class="sqs-app-bar-logo" alt="Logo" />
                <div class="sqs-app-bar-divider"></div>
                <?php endif; ?>
                <span class="sqs-app-bar-name">Data Editor</span>
            </div>
            <div class="sqs-app-bar-right">
                <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" class="sqs-bar-btn sqs-bar-btn-home">&#8592; Home</a>
                <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" class="sqs-bar-btn sqs-bar-btn-signout" id="sqsDataSignOutBtn">Sign Out</a>
            </div>
        </div>
        <script>
        (function(){
            var btn=document.getElementById('sqsDataSignOutBtn');
            if(!btn)return;
            btn.addEventListener('click',function(e){
                e.preventDefault();
                var dest=btn.href;
                var fd=new FormData();fd.append('action','sqs_signout');
                fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',{method:'POST',credentials:'same-origin',body:fd})
                .finally(function(){window.location.href=dest;});
            });
        })();
        </script>
        <div style="height:12px;background:#f5f7fb;"></div>
        <?php if ( $message ) : ?><div class="sqs-data-message"><?php echo esc_html( $message ); ?></div><?php endif; ?>
        <div class="sqs-data-tabs">
            <?php foreach ( $rows as $nav_row ) : ?><a href="#sqs-front-table-<?php echo esc_attr( $nav_row['data_key'] ); ?>"><?php echo esc_html( $nav_row['data_label'] ); ?></a><?php endforeach; ?>
        </div>
        <form method="post" id="sqs-frontend-form">
            <input type="hidden" name="sqs_frontend_action" value="save_all">
            <input type="hidden" name="sqs_frontend_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sqs_frontend_save' ) ); ?>">
            <div class="sqs-data-actions"><button class="sqs-data-btn" type="submit">Save All Tables</button><span class="sqs-data-note">Use the tabs to jump between tables. Changes are not saved until you click Save.</span></div>
            <div class="sqs-data-grid">
                <?php foreach ( $rows as $row ) :
                    $decoded = json_decode( $row['data_json'], true );
                    $json_for_attr = esc_attr( wp_json_encode( $decoded ) );
                    ?>
                    <div class="sqs-data-card sqs-editor-card" id="sqs-front-table-<?php echo esc_attr( $row['data_key'] ); ?>" data-key="<?php echo esc_attr( $row['data_key'] ); ?>">
                        <h2><?php echo esc_html( $row['data_label'] ); ?></h2>
                        <p class="sqs-data-note"><?php echo esc_html( sqs_pricing_calculator_admin_notes( $row['data_key'] ) ); ?> Last updated: <?php echo esc_html( $row['updated_at'] ); ?></p>
                        <input type="hidden" class="sqs-data-json" name="sqs_data[<?php echo esc_attr( $row['data_key'] ); ?>]" value="<?php echo esc_attr( wp_json_encode( $decoded ) ); ?>">
                        <div class="sqs-editor" data-key="<?php echo esc_attr( $row['data_key'] ); ?>" data-value="<?php echo $json_for_attr; ?>"></div>
                        <div class="sqs-data-tools"><button type="button" class="sqs-data-mini sqs-add-row">Add Row</button><button type="button" class="sqs-data-mini sqs-show-json">Advanced View</button></div>
                        <textarea class="sqs-hidden-json"><?php echo esc_textarea( wp_json_encode( $decoded, JSON_PRETTY_PRINT ) ); ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sqs-data-actions"><button class="sqs-data-btn" type="submit">Save All Tables</button></div>
        </form>
        <div class="sqs-data-foot">Quote Builder</div>
        <script>
        (function(){
            const data = <?php echo wp_json_encode( $all_data ); ?>;
            const matrixHeaders = {setupLbsTable:data.setupWidthBuckets||[],extrusionRateTable:data.prodWidthBuckets||[],inlineRateTable:data.prodWidthBuckets||[]};
            const listHeaders = {setupOpsExtrusion:data.setupWidthBuckets||[],inlineAdditionalOps:data.setupWidthBuckets||[],minimumSetupFees:data.setupWidthBuckets||[],zipperQtyPerHour:data.zipperWidthBuckets||[]};
            function parseValue(v){if(typeof v!=='string')return v;const t=v.trim();if(t==='')return '';if(/^-?\d+(\.\d+)?$/.test(t))return Number(t);return t;}
            function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
            function input(v,cls){return `<input type="text" class="${cls||'sqs-cell'}" value="${esc(v)}">`;}
            function renderMap(key,value){let rows=Object.entries(value||{}).map(([k,v])=>`<tr><td>${input(k,'sqs-map-key')}</td><td>${input(v,'sqs-map-value')}</td><td><button type="button" class="sqs-data-remove sqs-delete-row">Remove</button></td></tr>`).join('');return `<div class="sqs-data-table-wrap"><table class="sqs-data-table" data-kind="map"><thead><tr><th>Name</th><th>Value</th><th></th></tr></thead><tbody>${rows}</tbody></table></div>`;}
            function renderList(key,value){const headers=listHeaders[key];if(headers&&headers.length===(value||[]).length){let cells=value.map((v,i)=>`<td>${input(v,'sqs-list-value')}</td>`).join('');let head=headers.map(h=>`<th>${esc(h)}</th>`).join('');return `<div class="sqs-data-table-wrap"><table class="sqs-data-table" data-kind="list"><thead><tr>${head}</tr></thead><tbody><tr>${cells}</tr></tbody></table></div>`;}let rows=(value||[]).map(v=>`<tr><td>${input(v,'sqs-list-value')}</td><td><button type="button" class="sqs-data-remove sqs-delete-row">Remove</button></td></tr>`).join('');return `<div class="sqs-data-table-wrap"><table class="sqs-data-table" data-kind="list"><thead><tr><th>Value</th><th></th></tr></thead><tbody>${rows}</tbody></table></div>`;}
            function renderMatrix(key,value){const headers=matrixHeaders[key]||[];let head=`<th>Row</th>`+headers.map(h=>`<th>${esc(h)}</th>`).join('')+`<th></th>`;let rows=Object.entries(value||{}).map(([rowName,arr])=>{const cells=headers.map((h,i)=>`<td>${input((arr||[])[i]??'','sqs-matrix-value')}</td>`).join('');return `<tr><td class="sqs-row-label">${input(rowName,'sqs-matrix-key')}</td>${cells}<td><button type="button" class="sqs-data-remove sqs-delete-row">Remove</button></td></tr>`;}).join('');return `<div class="sqs-data-table-wrap"><table class="sqs-data-table" data-kind="matrix" data-headers='${esc(JSON.stringify(headers))}'><thead><tr>${head}</tr></thead><tbody>${rows}</tbody></table></div>`;}
            function renderDefaults(value){let html='';Object.entries(value||{}).forEach(([section,fields])=>{html+=`<h3>${esc(section.charAt(0).toUpperCase()+section.slice(1))} Defaults</h3><div class="sqs-data-table-wrap"><table class="sqs-data-table" data-kind="defaults" data-section="${esc(section)}"><thead><tr><th>Field</th><th>Default Value</th></tr></thead><tbody>`;Object.entries(fields||{}).forEach(([k,v])=>{html+=`<tr><td>${input(k,'sqs-default-key')}</td><td>${input(v,'sqs-default-value')}</td></tr>`;});html+=`</tbody></table></div>`;});return html;}
            function renderEditor(el){const key=el.dataset.key;const value=JSON.parse(el.dataset.value||'null');let html='';if(key==='defaults')html=renderDefaults(value);else if(matrixHeaders[key])html=renderMatrix(key,value);else if(Array.isArray(value))html=renderList(key,value);else if(value&&typeof value==='object')html=renderMap(key,value);else html='<p>Use Advanced View.</p>';el.innerHTML=html;}
            function serializeCard(card){const key=card.dataset.key;const editor=card.querySelector('.sqs-editor');let out;if(card.querySelector('.sqs-hidden-json').style.display==='block'){out=JSON.parse(card.querySelector('.sqs-hidden-json').value);}else if(key==='defaults'){out={};editor.querySelectorAll('table[data-kind="defaults"]').forEach(tbl=>{const section=tbl.dataset.section;out[section]={};tbl.querySelectorAll('tbody tr').forEach(tr=>{const k=tr.querySelector('.sqs-default-key').value.trim();if(k)out[section][k]=parseValue(tr.querySelector('.sqs-default-value').value);});});}else if(matrixHeaders[key]){out={};editor.querySelectorAll('tbody tr').forEach(tr=>{const k=tr.querySelector('.sqs-matrix-key').value.trim();if(k)out[k]=Array.from(tr.querySelectorAll('.sqs-matrix-value')).map(i=>parseValue(i.value));});}else if(Array.isArray(JSON.parse(editor.dataset.value||'[]'))){out=Array.from(editor.querySelectorAll('.sqs-list-value')).map(i=>parseValue(i.value)).filter(v=>v!=='');}else{out={};editor.querySelectorAll('tbody tr').forEach(tr=>{const kEl=tr.querySelector('.sqs-map-key');const vEl=tr.querySelector('.sqs-map-value');if(kEl&&vEl&&kEl.value.trim())out[kEl.value.trim()]=parseValue(vEl.value);});}card.querySelector('.sqs-data-json').value=JSON.stringify(out);card.querySelector('.sqs-hidden-json').value=JSON.stringify(out,null,2);}
            document.querySelectorAll('.sqs-editor').forEach(renderEditor);
            document.addEventListener('click',function(e){if(e.target.classList.contains('sqs-delete-row'))e.target.closest('tr').remove();if(e.target.classList.contains('sqs-add-row')){const card=e.target.closest('.sqs-editor-card');const key=card.dataset.key;const table=card.querySelector('.sqs-data-table');if(!table||key==='defaults'){alert('Rows are fixed for this table. Edit the existing cells.');return;}const kind=table.dataset.kind;if(kind==='map')table.querySelector('tbody').insertAdjacentHTML('beforeend',`<tr><td>${input('','sqs-map-key')}</td><td>${input('','sqs-map-value')}</td><td><button type="button" class="sqs-data-remove sqs-delete-row">Remove</button></td></tr>`);if(kind==='list')table.querySelector('tbody').insertAdjacentHTML('beforeend',`<tr><td>${input('','sqs-list-value')}</td><td><button type="button" class="sqs-data-remove sqs-delete-row">Remove</button></td></tr>`);if(kind==='matrix'){const headers=JSON.parse(table.dataset.headers||'[]');table.querySelector('tbody').insertAdjacentHTML('beforeend',`<tr><td class="sqs-row-label">${input('','sqs-matrix-key')}</td>${headers.map(()=>`<td>${input('','sqs-matrix-value')}</td>`).join('')}<td><button type="button" class="sqs-data-remove sqs-delete-row">Remove</button></td></tr>`);}}if(e.target.classList.contains('sqs-show-json')){const card=e.target.closest('.sqs-editor-card');try{serializeCard(card);}catch(err){alert('Advanced View has an error.');return;}const ta=card.querySelector('.sqs-hidden-json');ta.style.display=ta.style.display==='block'?'none':'block';e.target.textContent=ta.style.display==='block'?'Hide Advanced View':'Advanced View';}});
            document.getElementById('sqs-frontend-form').addEventListener('submit',function(e){try{document.querySelectorAll('.sqs-editor-card').forEach(serializeCard);}catch(err){e.preventDefault();alert('One of the tables has an invalid value. Please review Advanced View.');}});
        })();
        </script>
    </div>
    <?php
}


// ─── Core Quote Save / Load Layer ────────────────────────────────────────────

add_action( 'wp_ajax_sqs_save_quote', 'sqs_pricing_calculator_ajax_save_quote' );
add_action( 'wp_ajax_nopriv_sqs_save_quote', 'sqs_pricing_calculator_ajax_save_quote' );
add_action( 'wp_ajax_sqs_load_quotes', 'sqs_pricing_calculator_ajax_load_quotes' );
add_action( 'wp_ajax_nopriv_sqs_load_quotes', 'sqs_pricing_calculator_ajax_load_quotes' );
add_action( 'wp_ajax_sqs_get_quote', 'sqs_pricing_calculator_ajax_get_quote' );
add_action( 'wp_ajax_nopriv_sqs_get_quote', 'sqs_pricing_calculator_ajax_get_quote' );

function sqs_pricing_calculator_ajax_save_quote() {
    sqs_pricing_calculator_activate();
    if ( ! sqs_pricing_calculator_user_can_quote_ajax() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    $payload_raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
    $payload = json_decode( $payload_raw, true );
    if ( ! is_array( $payload ) ) {
        wp_send_json_error( array( 'message' => 'Quote data could not be read.' ), 400 );
    }

    global $wpdb;
    $quotes_table = sqs_pricing_calculator_quotes_table_name();
    $items_table  = sqs_pricing_calculator_quote_items_table_name();

    $quote_id = isset( $payload['quote_id'] ) ? absint( $payload['quote_id'] ) : 0;
    $new_revision = ! empty( $payload['new_revision'] );
    $status = sqs_pricing_calculator_clean_quote_status( isset( $payload['status'] ) ? $payload['status'] : 'Draft' );
    $result = isset( $payload['result'] ) && is_array( $payload['result'] ) ? $payload['result'] : array();
    $meta   = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();

    $product_type  = isset( $result['productName'] ) ? sanitize_text_field( $result['productName'] ) : '';
    $customer_name = isset( $meta['customerName'] ) ? sanitize_text_field( $meta['customerName'] ) : '';
    $company_name  = isset( $meta['companyName'] ) ? sanitize_text_field( $meta['companyName'] ) : '';
    $total_sales   = isset( $result['salesAmount'] ) ? (float) $result['salesAmount'] : 0;
    $now = current_time( 'mysql' );

    $existing = $quote_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $quotes_table WHERE id = %d", $quote_id ), ARRAY_A ) : null;

    $revision_number = 1;
    $quote_number = '';
    if ( $existing ) {
        $quote_number = $existing['quote_number'];
        $revision_number = absint( $existing['revision_number'] );
        if ( $new_revision ) {
            $max_revision = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(revision_number) FROM $quotes_table WHERE quote_number = %s", $quote_number ) );
            $revision_number = max( 1, absint( $max_revision ) + 1 );
            $quote_id = 0;
        }
    }

    $revision_label = sqs_pricing_calculator_revision_from_index( $revision_number );
    $payload['status'] = $status;
    $payload['revision_label'] = $revision_label;

    $row = array(
        'revision_number' => $revision_number,
        'revision_label'  => $revision_label,
        'status'          => $status,
        'product_type'    => $product_type,
        'customer_name'   => $customer_name,
        'company_name'    => $company_name,
        'total_sales'     => $total_sales,
        'quote_json'      => wp_json_encode( $payload ),
        'updated_at'      => $now,
    );

    if ( $quote_id && $existing && ! $new_revision ) {
        $wpdb->update( $quotes_table, $row, array( 'id' => $quote_id ) );
    } else {
        $row['quote_number'] = $quote_number;
        $row['created_at'] = $now;
        $wpdb->insert( $quotes_table, $row );
        $quote_id = absint( $wpdb->insert_id );
        if ( ! $quote_number ) {
            $quote_number = sqs_pricing_calculator_generate_quote_number( $quote_id );
            $wpdb->update( $quotes_table, array( 'quote_number' => $quote_number ), array( 'id' => $quote_id ) );
        }
    }

    $wpdb->delete( $items_table, array( 'quote_id' => $quote_id ) );
    $line_total = isset( $result['salesAmount'] ) ? (float) $result['salesAmount'] : 0;
    $quantity = isset( $result['quantity'] ) ? (float) $result['quantity'] : 0;
    $unit_price = isset( $result['unitPrice'] ) ? (float) $result['unitPrice'] : 0;
    $description = trim( $product_type . ' - ' . ( isset( $result['dimensions'] ) ? sanitize_text_field( $result['dimensions'] ) : '' ) );
    $wpdb->insert( $items_table, array(
        'quote_id'     => $quote_id,
        'line_number'  => 1,
        'product_type' => $product_type,
        'description'  => $description,
        'quantity'     => $quantity,
        'unit_price'   => $unit_price,
        'line_total'   => $line_total,
        'item_json'    => wp_json_encode( $result ),
        'created_at'   => $now,
    ) );

    do_action( 'sqs_quote_saved', $quote_id, $payload );

    wp_send_json_success( array(
        'quote_id'       => $quote_id,
        'quote_number'   => $quote_number,
        'revision_label' => $revision_label,
        'status'         => $status,
        'message'        => $quote_number . ' ' . $revision_label . ' saved as ' . $status . '.',
    ) );
}

function sqs_pricing_calculator_ajax_load_quotes() {
    sqs_pricing_calculator_activate();
    if ( ! sqs_pricing_calculator_user_can_quote_ajax() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );
    global $wpdb;
    $table = sqs_pricing_calculator_quotes_table_name();
    $rows = $wpdb->get_results( "SELECT id, quote_number, revision_label, status, product_type, customer_name, company_name, total_sales, updated_at FROM $table ORDER BY updated_at DESC LIMIT 75", ARRAY_A );
    wp_send_json_success( array( 'quotes' => $rows ? $rows : array() ) );
}

function sqs_pricing_calculator_ajax_get_quote() {
    sqs_pricing_calculator_activate();
    if ( ! sqs_pricing_calculator_user_can_quote_ajax() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );
    $quote_id = isset( $_POST['quote_id'] ) ? absint( $_POST['quote_id'] ) : 0;
    if ( ! $quote_id ) {
        wp_send_json_error( array( 'message' => 'Missing quote ID.' ), 400 );
    }
    global $wpdb;
    $table = sqs_pricing_calculator_quotes_table_name();
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $quote_id ), ARRAY_A );
    if ( ! $row ) {
        wp_send_json_error( array( 'message' => 'Quote not found.' ), 404 );
    }
    $payload = json_decode( $row['quote_json'], true );
    wp_send_json_success( array( 'quote' => $row, 'payload' => is_array( $payload ) ? $payload : array() ) );
}

// ─── Shortcode Registration ───────────────────────────────────────────────────

add_shortcode( 'sqs_pricing_calculator', 'sqs_pricing_calculator_shortcode' );

function sqs_pricing_calculator_shortcode() {
    sqs_pricing_calculator_maybe_start_session();
    if ( isset( $_POST['sqs_sales_action'] ) ) {
        if ( 'logout' === $_POST['sqs_sales_action'] ) {
            unset( $_SESSION['sqs_calc_sales_logged_in'] ); wp_safe_redirect( sqs_pricing_calculator_logout_url() ); exit;
        }
        if ( 'login' === $_POST['sqs_sales_action'] ) {
            $u  = isset($_POST['sqs_sales_user']) ? sanitize_text_field(wp_unslash($_POST['sqs_sales_user'])) : '';
            $p  = isset($_POST['sqs_sales_pass']) ? (string)wp_unslash($_POST['sqs_sales_pass']) : '';
            $su = get_option('sqs_calc_sales_user','sales_team');
            $sh = get_option('sqs_calc_sales_pass_hash','');
            if ( hash_equals((string)$su,(string)$u) && $sh && wp_check_password($p,$sh) ) {
                $_SESSION['sqs_calc_sales_logged_in'] = true;
            } else {
                ob_start(); sqs_pricing_calculator_render_sales_login('Login failed.'); return ob_get_clean();
            }
        }
    }
    if ( empty( $_SESSION['sqs_calc_sales_logged_in'] ) ) {
        ob_start(); sqs_pricing_calculator_render_sales_login(); return ob_get_clean();
    }
    ob_start(); sqs_pricing_calculator_render(); return ob_get_clean();
}

function sqs_pricing_calculator_render_sales_login( $msg = '' ) {
    $co = get_option('sqs_company_name','Quote Builder');
    $lg = get_option('sqs_logo_url','');
    ?>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700;800&display=swap" rel="stylesheet"/>
    <style>.sqs-lw{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#edf0f4;font-family:'DM Sans',sans-serif}.sqs-lc{background:#fff;border-radius:20px;padding:48px 40px;width:100%;max-width:400px;box-shadow:0 8px 32px rgba(10,15,30,.12);border:1px solid #e2e5eb}.sqs-lc img{display:block;max-width:200px;height:auto;margin:0 auto 16px}.sqs-lk{font-size:10px;font-weight:700;color:#c62828;text-transform:uppercase;letter-spacing:.14em;text-align:center;margin-bottom:8px}.sqs-lt{font-size:22px;font-weight:800;color:#0f1623;text-align:center;margin:0 0 4px}.sqs-ls{font-size:13px;color:#667085;text-align:center;margin:0 0 24px}.sqs-lf{margin-bottom:14px}.sqs-lf label{display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}.sqs-lf input{width:100%;border:1px solid #d0d5dd;border-radius:10px;padding:11px 14px;font-size:14px;color:#0f1623;box-sizing:border-box}.sqs-lb{width:100%;background:#c62828;color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;margin-top:6px}.sqs-le{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px;font-size:13px;color:#c62828;font-weight:600;margin-bottom:14px;text-align:center}</style>
    <div class="sqs-lw"><div class="sqs-lc">
        <?php if($lg):?><img src="<?php echo esc_url($lg);?>" alt="<?php echo esc_attr($co);?>"/><?php endif;?>
        <div class="sqs-lk">&#9679; Sales Portal</div>
        <h2 class="sqs-lt">Quote Builder</h2>
        <p class="sqs-ls">Sign in to access your account</p>
        <?php if($msg):?><div class="sqs-le"><?php echo esc_html($msg);?></div><?php endif;?>
        <form method="post">
            <input type="hidden" name="sqs_sales_action" value="login"/>
            <div class="sqs-lf"><label>Username</label><input type="text" name="sqs_sales_user" required/></div>
            <div class="sqs-lf"><label>Password</label><input type="password" name="sqs_sales_pass" required/></div>
            <button class="sqs-lb" type="submit">Sign In &rarr;</button>
        </form>
        <div style="text-align:center;margin-top:16px;">
            <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:13px 20px;background:#0f1623;color:#ffffff;border:2px solid #0f1623;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;width:100%;box-sizing:border-box;letter-spacing:.02em;">&#8592; Return to Home</a>
        </div>
    </div></div>
    <?php
}

// ─── Renderer ─────────────────────────────────────────────────────────────────

function sqs_pricing_calculator_render() {
    $sqs_data_json = wp_json_encode( sqs_pricing_calculator_get_data() );
    $sqs_quote_ajax_url = admin_url( 'admin-ajax.php' );
    $sqs_quote_nonce = wp_create_nonce( 'sqs_quote_actions' );
    // Unique prefix to avoid conflicts with other page scripts/styles
    $uid = 'sqs_calc_' . uniqid();
    ?>
    <style>
    /* ── Scoped to .sqs-calc wrapper ── */
    .sqs-calc {
        --sqc-bg: #f4f6f8;
        --sqc-bg-accent: #fafbfc;
        --sqc-card: #ffffff;
        --sqc-line: #dde3ea;
        --sqc-line-strong: #cfd7e2;
        --sqc-text: #1f2937;
        --sqc-muted: #66758a;
        --sqc-red: #c62828;
        --sqc-red-dark: #a81d1d;
        --sqc-red-soft: #fff1f1;
        --sqc-red-soft-2: #fde8e8;
        --sqc-green: #166534;
        --sqc-green-soft: #ecfdf3;
        --sqc-warn: #92400e;
        --sqc-warn-soft: #fff7df;
        --sqc-shadow-sm: 0 6px 18px rgba(15,23,42,.05);
        --sqc-shadow-md: 0 14px 34px rgba(15,23,42,.08);

        box-sizing: border-box;
        font-family: Arial, Helvetica, sans-serif;
        color: var(--sqc-text);
        background:
            radial-gradient(circle at top center, rgba(148,163,184,.18) 0, rgba(148,163,184,0) 34%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        padding: 22px 18px 30px;
        border-radius: 12px;
    }
    .sqs-calc *,
    .sqs-calc *::before,
    .sqs-calc *::after { box-sizing: border-box; }

    /* Demo Mode */
    .sqs-calc .sqc-demo-corner {
        position: fixed;
        bottom: 24px;
        left: 24px;
        z-index: 9000;
        cursor: default;
    }
    .sqs-calc .sqc-demo-corner-pill {
        background: linear-gradient(135deg,#7c3aed,#4f46e5);
        color: white;
        font-size: 11px;
        font-weight: 800;
        padding: 8px 16px;
        border-radius: 999px;
        box-shadow: 0 4px 16px rgba(124,58,237,.4);
        letter-spacing: .08em;
        text-transform: uppercase;
        display: inline-block;
        transition: opacity .2s;
    }
    .sqs-calc .sqc-demo-corner-expanded {
        display: none;
        position: absolute;
        bottom: calc(100% + 10px);
        left: 0;
        background: linear-gradient(135deg,#7c3aed,#4f46e5);
        color: white;
        padding: 14px 16px;
        border-radius: 14px;
        box-shadow: 0 8px 28px rgba(124,58,237,.45);
        width: 240px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .sqs-calc .sqc-demo-corner:hover .sqc-demo-corner-pill { opacity: .85; }
    .sqs-calc .sqc-demo-corner:hover .sqc-demo-corner-expanded { display: block; }

    /* Topbar */
    .sqs-calc .sqc-topbar { display:block; text-align:center; margin-bottom:10px; }
    .sqs-calc .sqc-brand-logo {
        display:block; max-width:336px; width:100%; height:auto;
        margin:0 auto 35px;
        filter:drop-shadow(0 4px 14px rgba(0,0,0,.06));
    }
    .sqs-calc .sqc-title h1 {
        margin:0; font-size:34px; line-height:1.1; color:#333; font-weight:800;
        letter-spacing:-.02em; padding-bottom:50px;
    }

    /* Buttons */
    .sqs-calc button {
        border:1px solid var(--sqc-line-strong); background:var(--sqc-card);
        color:var(--sqc-text); padding:10px 14px; border-radius:12px;
        font-weight:700; cursor:pointer;
        transition:background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .18s;
        box-shadow:0 1px 0 rgba(255,255,255,.8) inset;
    }
    .sqs-calc button:hover {
        border-color:var(--sqc-red); color:var(--sqc-red); background:#fff7f7;
        box-shadow:0 8px 20px rgba(198,40,40,.10); transform:translateY(-1px);
    }
    .sqs-calc button.sqc-primary {
        background:linear-gradient(180deg, var(--sqc-red) 0%, var(--sqc-red-dark) 100%);
        color:white; border-color:var(--sqc-red);
        box-shadow:0 10px 20px rgba(198,40,40,.18);
    }
    .sqs-calc button.sqc-primary:hover {
        background:linear-gradient(180deg, #b62525 0%, #931919 100%);
        border-color:#931919; color:white;
    }
    .sqs-calc .sqc-switch-btn.sqc-active {
        background:linear-gradient(180deg, #fff4f4 0%, #ffe9e9 100%);
        border-color:var(--sqc-red); color:var(--sqc-red);
        box-shadow:0 0 0 1px rgba(198,40,40,.08), 0 10px 22px rgba(198,40,40,.08);
    }

    /* Product switch */
    .sqs-calc .sqc-product-switch { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:8px; }

    /* Layout */
    .sqs-calc .sqc-bottom-stack { display:grid; gap:12px; }
    /* product switch stays 3-col */
    .sqs-calc .sqc-product-switch { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:0; }

    /* Top pricing grid */
    .sqs-calc .sqc-top-pricing-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .sqs-calc .sqc-display-card.sqc-highlight {
        background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border-color:rgba(148,163,184,.30);
    }
    .sqs-calc .sqc-display-card.sqc-highlight label { color:#166534; }
    .sqs-calc .sqc-display-card.sqc-highlight .sqc-display-value { background:rgba(255,255,255,.7); border-color:#86efac; color:#166534; }
    .sqs-calc .sqc-input-card {
        background:linear-gradient(180deg, #fff8f8 0%, var(--sqc-red-soft-2) 100%);
        border:1px solid #efc4c4; border-radius:18px; padding:14px;
        box-shadow:0 10px 24px rgba(15,23,42,.05);
    }
    .sqs-calc .sqc-input-card label { color:var(--sqc-red); margin-bottom:8px; }
    .sqs-calc .sqc-input-card input {
        border-color:#e8b3b3; background:rgba(255,255,255,.97);
        font-size:20px; font-weight:800; padding:12px 14px;
        box-shadow:0 1px 0 rgba(255,255,255,.9) inset;
    }

    /* ── Compact cards used inside Target Pricing & Profit Margins card ── */
    .sqs-calc .sqc-sm-input-card {
        background:linear-gradient(180deg,#fff8f8 0%,var(--sqc-red-soft-2) 100%);
        border:1px solid #efc4c4; border-radius:12px; padding:10px 12px;
    }
    .sqs-calc .sqc-sm-input-card label {
        display:block; font-size:10px; font-weight:800; color:var(--sqc-red);
        text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px;
    }
    .sqs-calc .sqc-sm-input-card input {
        width:100%; border:1px solid #e8b3b3; border-radius:8px;
        padding:7px 9px; font-size:14px; font-weight:800;
        background:rgba(255,255,255,.97); color:var(--sqc-text);
        transition:border-color .18s,box-shadow .18s;
    }
    .sqs-calc .sqc-sm-input-card input:focus {
        outline:none; border-color:var(--sqc-red);
        box-shadow:0 0 0 3px rgba(198,40,40,.10);
    }
    .sqs-calc .sqc-sm-display-card {
        border-radius:12px; padding:10px 12px;
        background:linear-gradient(180deg,#f8fafc 0%,#f0f4f8 100%);
        border:1px solid var(--sqc-line);
    }
    .sqs-calc .sqc-sm-display-card.sqc-sm-highlight {
        background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
        border-color:rgba(148,163,184,.30);
    }
    .sqs-calc .sqc-sm-label {
        font-size:10px; font-weight:800; color:#166534;
        text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px;
    }
    .sqs-calc .sqc-sm-display-card:not(.sqc-sm-highlight) .sqc-sm-label {
        color:var(--sqc-muted);
    }
    .sqs-calc .sqc-sm-value {
        font-size:14px; font-weight:800; color:#166534; line-height:1.2;
    }
    .sqs-calc .sqc-sm-display-card:not(.sqc-sm-highlight) .sqc-sm-value {
        color:var(--sqc-text);
    }
    @media (max-width:1200px) {
        .sqs-calc .sqc-pm-grid { grid-template-columns:1fr 1fr !important; }
    }

    /* Read-only display cards in the top row */
    .sqs-calc .sqc-display-card {
        background:linear-gradient(180deg, #f8fafc 0%, #f0f4f8 100%);
        border:1px solid var(--sqc-line); border-radius:18px; padding:14px;
        box-shadow:0 4px 12px rgba(15,23,42,.04);
    }
    .sqs-calc .sqc-display-card label {
        color:var(--sqc-muted); margin-bottom:8px;
    }
    .sqs-calc .sqc-display-card .sqc-display-value {
        font-size:20px; font-weight:800; padding:12px 14px;
        background:rgba(255,255,255,.6); border:1px solid var(--sqc-line);
        border-radius:12px; color:var(--sqc-text); line-height:1.2;
        min-height:48px; display:flex; align-items:center;
        user-select:text;
    }

    /* Cards */
    .sqs-calc .sqc-card {
        background:linear-gradient(180deg,#fff 0%,#fcfdff 100%);
        border:1px solid var(--sqc-line); border-radius:18px; padding:14px;
        box-shadow:var(--sqc-shadow-sm);
    }
    .sqs-calc .sqc-card.sqc-soft { background:linear-gradient(180deg,#fff 0%,#fbfcff 100%); }
    .sqs-calc .sqc-card.sqc-tight { padding:14px 16px; }
    .sqs-calc .sqc-card h2 { font-size:22px; letter-spacing:-.01em; margin-top:0; }
    .sqs-calc .sqc-card h3 { font-size:16px; margin-bottom:10px; margin-top:0; }

    .sqs-calc .sqc-section-kicker {
        font-size:11px; color:var(--sqc-red); text-transform:uppercase;
        letter-spacing:.10em; font-weight:800; margin-bottom:6px;
    }
    .sqs-calc .sqc-section-title { display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:10px; }
    .sqs-calc .sqc-section-title small { color:var(--sqc-muted); font-weight:700; }

    /* Form fields */
    .sqs-calc .sqc-field-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .sqs-calc .sqc-field-grid.sqc-three { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .sqs-calc .sqc-field.sqc-full { grid-column:1/-1; }
    .sqs-calc label {
        display:block; font-size:11px; font-weight:800; color:var(--sqc-muted);
        margin-bottom:6px; text-transform:uppercase; letter-spacing:.05em;
    }
    .sqs-calc input,
    .sqs-calc select,
    .sqs-calc textarea {
        width:100%; border:1px solid var(--sqc-line-strong); border-radius:12px;
        padding:10px 12px; font-size:14px; background:#fff; color:var(--sqc-text);
        transition:border-color .18s, box-shadow .18s, background-color .18s, transform .18s;
    }
    .sqs-calc input:hover, .sqs-calc select:hover, .sqs-calc textarea:hover {
        border-color:#d98b8b; background:#fffdfd;
    }
    .sqs-calc input:focus, .sqs-calc select:focus, .sqs-calc textarea:focus {
        outline:none; border-color:var(--sqc-red);
        box-shadow:0 0 0 4px rgba(198,40,40,.10); transform:translateY(-1px);
    }
    .sqs-calc textarea { min-height:90px; resize:vertical; }

    /* Sticky / sticky panel */
    .sqs-calc .sqc-sticky-panel { position:sticky; top:16px; }
    .sqs-calc .sqc-live-card {
        background:
            linear-gradient(180deg, rgba(248,250,252,.98) 0%, rgba(226,232,240,.92) 100%);
        border:1px solid rgba(100,116,139,.26);
        border-top:4px solid #b91c1c;
        border-radius:22px;
        box-shadow:
            0 1px 0 rgba(255,255,255,.98) inset,
            0 -1px 0 rgba(15,23,42,.06) inset,
            0 0 0 1px rgba(185,28,28,.045),
            0 18px 42px rgba(15,23,42,.13);
        backdrop-filter:blur(10px);
    }
    .sqs-calc .sqc-live-card::before {
        content:'';
        position:absolute;
        left:18px;
        right:18px;
        top:0;
        height:1px;
        background:linear-gradient(90deg, transparent, rgba(255,255,255,.95), transparent);
        pointer-events:none;
    }
    .sqs-calc .sqc-live-card::after {
        display:none;
    }
    .sqs-calc .sqc-live-card .sqc-section-kicker {
        color:#991b1b;
        letter-spacing:.13em;
    }
    .sqs-calc .sqc-live-card .sqc-toolbar button {
        border-radius:13px;
        box-shadow:
            0 1px 0 rgba(255,255,255,.9) inset,
            0 7px 18px rgba(15,23,42,.08);
        transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .sqs-calc .sqc-live-card .sqc-toolbar button:hover {
        transform:translateY(-1px);
        box-shadow:
            0 1px 0 rgba(255,255,255,.9) inset,
            0 12px 24px rgba(15,23,42,.13);
    }
    .sqs-calc .sqc-live-card .sqc-kpi {
        position:relative;
        overflow:hidden;
        background:
            linear-gradient(180deg, #ffffff 0%, #f3f6fa 100%);
        border:1px solid rgba(100,116,139,.28);
        border-radius:18px;
        box-shadow:
            0 1px 0 rgba(255,255,255,.98) inset,
            0 -1px 0 rgba(15,23,42,.05) inset,
            0 9px 20px rgba(15,23,42,.065);
        transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .sqs-calc .sqc-live-card .sqc-kpi::before {
        content:'';
        position:absolute;
        top:0;
        left:0;
        right:0;
        height:3px;
        background:linear-gradient(90deg, rgba(100,116,139,.16), rgba(100,116,139,.34), rgba(100,116,139,.16));
        pointer-events:none;
    }
    .sqs-calc .sqc-live-card .sqc-kpi:hover {
        transform:translateY(-2px);
        box-shadow:
            0 1px 0 rgba(255,255,255,.98) inset,
            0 -1px 0 rgba(15,23,42,.05) inset,
            0 15px 30px rgba(15,23,42,.11);
        border-color:rgba(71,85,105,.40);
    }
    .sqs-calc .sqc-live-card .sqc-kpi.sqc-good,
    .sqs-calc .sqc-live-card .sqc-kpi.sqc-warn {
        background:
            linear-gradient(180deg, #ffffff 0%, #f3f6fa 100%);
        border-color:rgba(100,116,139,.28);
    }
    .sqs-calc .sqc-live-card .sqc-kpi.sqc-good::before {
        background:linear-gradient(90deg, rgba(22,101,52,.12), rgba(22,101,52,.38), rgba(22,101,52,.12));
    }
    .sqs-calc .sqc-live-card .sqc-kpi.sqc-warn::before {
        background:linear-gradient(90deg, rgba(185,28,28,.12), rgba(185,28,28,.38), rgba(185,28,28,.12));
    }
    .sqs-calc .sqc-live-card .sqc-kpi .sqc-label {
        font-size:10px;
        font-weight:800;
        letter-spacing:.09em;
        text-transform:uppercase;
        color:#64748b;
    }
    .sqs-calc .sqc-live-card .sqc-kpi .sqc-value {
        font-size:31px;
        font-weight:850;
        letter-spacing:-.04em;
        color:#0f172a;
        text-shadow:0 1px 0 rgba(255,255,255,.85);
    }
    .sqs-calc .sqc-live-card .sqc-kpi:first-child .sqc-value {
        font-size:36px;
        color:#020617;
    }
    .sqs-calc .sqc-live-card .sqc-kpi:first-child {
        border-color:rgba(71,85,105,.36);
        box-shadow:
            0 1px 0 rgba(255,255,255,.98) inset,
            0 -1px 0 rgba(15,23,42,.05) inset,
            0 12px 26px rgba(15,23,42,.095);
    }
    .sqs-calc .sqc-live-card .sqc-kpi.sqc-good .sqc-value,
    .sqs-calc .sqc-live-card .sqc-kpi.sqc-warn .sqc-value {
        color:#0f172a;
    }

    /* KPI */
    .sqs-calc .sqc-kpi-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-bottom:10px; }
    .sqs-calc .sqc-kpi { border:1px solid var(--sqc-line); border-radius:16px; padding:14px; background:#fff; box-shadow:0 1px 0 rgba(255,255,255,.8) inset; }
    .sqs-calc .sqc-kpi .sqc-label { font-size:11px; font-weight:800; color:var(--sqc-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; }
    .sqs-calc .sqc-kpi .sqc-value { font-size:26px; font-weight:800; line-height:1.05; }
    .sqs-calc .sqc-kpi.sqc-good { background:linear-gradient(180deg,#f4fff7 0%,var(--sqc-green-soft) 100%); border-color:#b7ebc8; }
    .sqs-calc .sqc-kpi.sqc-warn { background:linear-gradient(180deg,#fffdf5 0%,var(--sqc-warn-soft) 100%); border-color:#f5d87a; }

    /* Result callout */
    .sqs-calc .sqc-result-callout {
        padding:16px; border:1px solid #efc9c9; border-radius:18px;
        background:linear-gradient(135deg,#fff8f8 0%,#fff 54%,#fff3f3 100%);
        box-shadow:0 16px 34px rgba(198,40,40,.08); margin-bottom:12px;
    }
    .sqs-calc .sqc-result-callout .sqc-eyebrow {
        font-size:11px; color:var(--sqc-red); text-transform:uppercase;
        letter-spacing:.10em; font-weight:800; margin-bottom:8px;
    }
    .sqs-calc .sqc-result-callout .sqc-big {
        font-size:42px; line-height:.98; font-weight:800; margin-bottom:10px; letter-spacing:-.03em;
    }

    /* Mini stats */
    .sqs-calc .sqc-mini-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px; }
    .sqs-calc .sqc-mini-stat { border:1px solid var(--sqc-line); border-radius:14px; padding:10px 12px; background:rgba(255,255,255,.88); }
    .sqs-calc .sqc-mini-stat .sqc-label { font-size:11px; color:var(--sqc-muted); text-transform:uppercase; font-weight:800; letter-spacing:.05em; margin-bottom:4px; }
    .sqs-calc .sqc-mini-stat .sqc-value { font-size:18px; font-weight:800; }

    /* Split two */
    .sqs-calc .sqc-split-two { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

    /* Tables */
    .sqs-calc table { width:100%; border-collapse:separate; border-spacing:0; font-size:14px; overflow:hidden; border-radius:14px; }
    .sqs-calc th, .sqs-calc td { padding:11px 10px; border-bottom:1px solid var(--sqc-line); text-align:left; vertical-align:top; background:rgba(255,255,255,.8); }
    .sqs-calc th { font-size:11px; color:var(--sqc-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:800; background:#f8fafc; }
    .sqs-calc tr:hover td { background:#fcfcfd; }

    /* Toolbar */
    .sqs-calc .sqc-toolbar { display:flex; gap:10px; flex-wrap:wrap; }

    /* Misc */
    .sqs-calc .sqc-muted { color:var(--sqc-muted); }
    .sqs-calc .sqc-note { font-size:13px; line-height:1.55; color:var(--sqc-muted); }
    .sqs-calc .sqc-hr { height:1px; background:var(--sqc-line); margin:12px 0; }
    .sqs-calc .sqc-badge { display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:800; background:var(--sqc-red-soft-2); color:var(--sqc-red); }
    .sqs-calc .sqc-small { font-size:12px; }
    .sqs-calc .sqc-hidden { display:none !important; }

    /* Quote Details card — screen-only fields */
    .sqs-calc .sqc-qd-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
    .sqs-calc .sqc-qd-full { grid-column:1/-1; }

    /* Print section (hidden until add-on injects it) */
    .sqs-calc .sqc-print-only { display:none; }

    /* Responsive */
    @media (max-width:900px) {
        .sqs-calc .sqc-sticky-panel { position:static; }
        .sqs-calc .sqc-split-two,
        .sqs-calc .sqc-field-grid,
        .sqs-calc .sqc-field-grid.sqc-three,
        .sqs-calc .sqc-mini-grid,
        .sqs-calc .sqc-product-switch { grid-template-columns:1fr 1fr; }
        .sqs-calc .sqc-pm-grid { grid-template-columns:1fr 1fr !important; }
        .sqs-calc .sqc-title h1 { font-size:26px; }
        .sqs-calc .sqc-qd-grid { grid-template-columns:1fr 1fr; }
    }
    @media (max-width:600px) {
        .sqs-calc .sqc-split-two,
        .sqs-calc .sqc-field-grid,
        .sqs-calc .sqc-field-grid.sqc-three,
        .sqs-calc .sqc-product-switch,
        .sqs-calc .sqc-qd-grid { grid-template-columns:1fr; }
    }
    /* Print CSS is owned by the Quote Builder Print add-on plugin */
    </style>

    <div class="sqs-calc" id="<?php echo esc_attr( $uid ); ?>">

        

        <!-- Hidden: quote number managed by the save system -->
        <div class="sqc-hidden" aria-hidden="true">
            <input id="quoteNumber" value="" />
        </div>

        <!-- ══ UNIFIED APP TOP BAR — Sales Calculator ══ -->
        <div class="sqs-app-bar sqc-screen-only">
            <div class="sqs-app-bar-left">
                <?php $bar_logo = get_option('sqs_logo_url',''); if($bar_logo): ?>
                <img src="<?php echo esc_url($bar_logo); ?>" class="sqs-app-bar-logo" alt="Logo" />
                <div class="sqs-app-bar-divider"></div>
                <?php endif; ?>
                <span class="sqs-app-bar-name">Quote Builder</span>
                <div class="sqs-app-bar-divider"></div>
                <span class="sqs-app-bar-product" id="productLabel">Tubing</span>
            </div>
            <div class="sqs-app-bar-right">
                <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" class="sqs-bar-btn sqs-bar-btn-home">&#8592; Home</a>
                <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" class="sqs-bar-btn sqs-bar-btn-signout" id="sqsSignOutBtn">Sign Out</a>
            </div>
        </div>
        <script>
        (function(){
            var btn=document.getElementById('sqsSignOutBtn');
            if(!btn)return;
            btn.addEventListener('click',function(e){
                e.preventDefault();
                var dest=btn.href;
                var fd=new FormData();fd.append('action','sqs_signout');
                fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',{method:'POST',credentials:'same-origin',body:fd})
                .finally(function(){window.location.href=dest;});
            });
        })();
        </script>

        <!-- Quote action toolbar -->
        <div class="sqs-quote-toolbar sqc-screen-only">
            <button id="saveQuoteBtn" type="button" class="sqs-bar-btn sqs-bar-btn-primary">Save Quote</button>
            <button id="newRevisionBtn" type="button" class="sqs-bar-btn sqs-bar-btn-blue">New Revision</button>
            <button id="loadQuoteBtn" type="button" class="sqs-bar-btn sqs-bar-btn-orange">Load Quote</button>
            <div class="sqs-bar-sep"></div>
            <button id="printBtn" type="button" class="sqs-bar-btn">&#128438; Print Quote</button>
            <span class="sqs-status-control"><span class="sqs-status-label">Status</span><select id="quoteStatus" class="sqs-bar-select" aria-label="Quote Status"><option>Draft</option><option>Final</option><option>Archived</option></select></span>
            <span id="quoteSaveStamp" class="sqs-bar-status">Unsaved</span>
            <span class="sqs-toolbar-spacer"></span>
            <?php do_action('sqs_toolbar_extra_buttons'); ?>
            <button id="resetDefaultsBtn" type="button" class="sqs-bar-btn sqs-bar-btn-home">Reset</button>
        </div>

        <div id="sqsLoadModal" class="sqs-load-modal sqc-screen-only" role="dialog" aria-modal="true" aria-label="Load Quote">
            <div class="sqs-load-panel">
                <div class="sqs-load-head">
                    <h2>Load Quote</h2>
                    <button type="button" id="sqsLoadClose" class="sqs-load-close">Close</button>
                </div>
                <div class="sqs-load-body" id="sqsLoadBody">Loading saved quotes...</div>
            </div>
        </div>

        <!-- ══ LIVE PRICING CARD ══ -->
        <div class="sqc-card sqc-live-card sqc-screen-only" style="position:relative;z-index:98;margin-bottom:0;border-radius:0;border-top:none;box-shadow:0 2px 12px rgba(15,23,42,.06);">

            <!-- Margin inputs row — compact inline style -->
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <label id="topMarginLabel" style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Profit Margin</label>
                    <input id="topProfitMargin" type="text" inputmode="decimal" style="width:90px;font-size:14px;font-weight:800;padding:6px 10px;border:1px solid rgba(100,116,139,.28);border-radius:10px;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);color:#0f172a;text-align:center;box-shadow:0 1px 0 rgba(255,255,255,.98) inset,0 -1px 0 rgba(15,23,42,.035) inset;" />
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label id="topUpchargeLabel" style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Regular Upcharge</label>
                    <input id="topUpcharge" type="text" inputmode="decimal" style="width:90px;font-size:14px;font-weight:800;padding:6px 10px;border:1px solid rgba(100,116,139,.28);border-radius:10px;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);color:#0f172a;text-align:center;box-shadow:0 1px 0 rgba(255,255,255,.98) inset,0 -1px 0 rgba(15,23,42,.035) inset;" />
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Final Margin</span>
                    <span id="topFinalMargin" style="font-size:14px;font-weight:800;color:#0f172a;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);border:1px solid rgba(100,116,139,.28);border-radius:10px;padding:6px 12px;min-width:70px;text-align:center;display:inline-block;box-shadow:0 1px 0 rgba(255,255,255,.98) inset,0 -1px 0 rgba(15,23,42,.035) inset;">—</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span id="topUnitPriceLabel" style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Price / Roll</span>
                    <span id="topUnitPrice" style="font-size:14px;font-weight:800;color:#0f172a;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);border:1px solid rgba(100,116,139,.28);border-radius:10px;padding:6px 12px;min-width:80px;text-align:center;display:inline-block;box-shadow:0 1px 0 rgba(255,255,255,.98) inset,0 -1px 0 rgba(15,23,42,.035) inset;">—</span>
                </div>
            </div>

            <!-- KPI row: Sales | Cost | Net Revenue | Price/Roll | Margin -->
            <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;">
                <div class="sqc-kpi sqc-good">
                    <div class="sqc-label">Total Sales</div>
                    <div class="sqc-value" id="heroSales">$0.00</div>
                </div>
                <div class="sqc-kpi">
                    <div class="sqc-label">Total Cost</div>
                    <div class="sqc-value" id="kpiCost">$0.00</div>
                </div>
                <div class="sqc-kpi sqc-good">
                    <div class="sqc-label">Net Revenue</div>
                    <div class="sqc-value" id="heroNetRevenue">$0.00</div>
                </div>
                <div class="sqc-kpi">
                    <div class="sqc-label" id="kpiUnitLabel">Price / Roll</div>
                    <div class="sqc-value" id="kpiUnitPrice">$0.00</div>
                </div>
                <div class="sqc-kpi sqc-warn">
                    <div class="sqc-label">Profit Margin</div>
                    <div class="sqc-value" id="kpiMargin">0.00%</div>
                </div>
            </div>
        </div>

        <!-- ══ PRODUCT SELECTOR + INPUTS — full width, 3-col fields ══ -->
        <div class="sqc-card sqc-screen-only" style="margin-bottom:16px;">
            <div class="sqc-section-kicker">Product</div>
            <div class="sqc-product-switch" style="margin-bottom:16px;">
                <button class="sqc-switch-btn sqc-active" data-product="tubing">Tubing</button>
                <button class="sqc-switch-btn" data-product="inline">In-Line BSB</button>
                <button class="sqc-switch-btn" data-product="zipper">Zipper</button>
            </div>
            <div class="sqc-section-kicker" style="margin-top:4px;">Inputs</div>
            <div style="font-size:18px;font-weight:800;margin-bottom:12px;" id="inputTitle">Tubing Variables</div>
            <div id="inputs-tubing" class="sqc-product-inputs"></div>
            <div id="inputs-inline" class="sqc-product-inputs sqc-hidden"></div>
            <div id="inputs-zipper" class="sqc-product-inputs sqc-hidden"></div>
        </div>

        <!-- Bottom stack -->
        <div class="sqc-bottom-stack">

            <!-- Target Pricing and Profit Margins card -->
            <div class="sqc-card sqc-screen-only" style="border-top:3px solid #b91c1c;box-shadow:0 1px 0 rgba(255,255,255,.95) inset, 0 10px 28px rgba(15,23,42,.07);">
                <div class="sqc-section-kicker">Analysis</div>
                <h2 style="font-size:20px;font-weight:800;margin:0 0 14px;">Target Pricing and Profit Margins</h2>

                <!-- Row 1: Profit Margins — all GREEN display, fed from sticky card inputs -->
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--sqc-muted);margin-bottom:6px;">Profit Margins</div>
                <div class="sqc-pm-grid" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px;margin-bottom:8px;">
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label" id="topMarginLabelB">Profit Margin</div>
                        <div id="topProfitMarginDisplay" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label" id="topUpchargeLabelB">Regular Upcharge</div>
                        <div id="topUpchargeDisplay" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Final Profit Margin</div>
                        <div id="topFinalMarginB" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Profit</div>
                        <div id="topProfit" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Price / Roll</div>
                        <div id="topUnitPriceB" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Price / LBS</div>
                        <div id="topPricePerLbs" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Total Sales Amount</div>
                        <div id="topTotalSales" class="sqc-sm-value">—</div>
                    </div>
                </div>

                <!-- Row 2: Target by Price/Roll -->
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--sqc-muted);margin-bottom:6px;">Enter Target Pricing — By Roll</div>
                <div class="sqc-pm-grid" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px;margin-bottom:8px;">
                    <div class="sqc-sm-input-card">
                        <label id="tgt1UnitLabel">Target Price / Roll</label>
                        <input id="tgt1UnitPrice" type="text" inputmode="decimal" />
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Profit</div>
                        <div id="tgt1Profit" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Price / LBS</div>
                        <div id="tgt1PricePerLbs" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Total Sales</div>
                        <div id="tgt1TotalSales" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Profit Margin</div>
                        <div id="tgt1Margin" class="sqc-sm-value">—</div>
                    </div>
                    <div style="visibility:hidden;"></div>
                    <div style="visibility:hidden;"></div>
                </div>

                <!-- Row 3: Target by Price/LBS -->
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--sqc-muted);margin-bottom:6px;">Enter Target Pricing — By LBS</div>
                <div class="sqc-pm-grid" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px;margin-bottom:16px;">
                    <div class="sqc-sm-input-card">
                        <label>Target Price / LBS</label>
                        <input id="tgt2PricePerLbs" type="text" inputmode="decimal" />
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Profit</div>
                        <div id="tgt2Profit" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label" id="tgt2UnitLabel">Price / Roll</div>
                        <div id="tgt2UnitPrice" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Total Sales</div>
                        <div id="tgt2TotalSales" class="sqc-sm-value">—</div>
                    </div>
                    <div class="sqc-sm-display-card sqc-sm-highlight">
                        <div class="sqc-sm-label">Profit Margin</div>
                        <div id="tgt2Margin" class="sqc-sm-value">—</div>
                    </div>
                    <div style="visibility:hidden;"></div>
                    <div style="visibility:hidden;"></div>
                </div>

                <hr style="border:none;border-top:1px solid var(--sqc-line);margin:0 0 16px;" />

                <!-- Margin-Driven + Target Output tables -->
                <div class="sqc-split-two">
                    <div>
                        <h3 style="font-size:13px;font-weight:700;color:var(--sqc-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Margin-Driven Output</h3>
                        <table id="marginOutputTable"></table>
                    </div>
                    <div>
                        <h3 style="font-size:13px;font-weight:700;color:var(--sqc-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Target Price Output</h3>
                        <table id="targetOutputTable"></table>
                    </div>
                </div>
            </div>

            <!-- Price Breaks card -->
            <div class="sqc-card sqc-screen-only">
                <div class="sqc-section-kicker">Volume</div>
                <div class="sqc-section-title"><h2>Price Breaks</h2></div>
                <table id="priceBreakTable"></table>
            </div>

            <!-- Quote Info — core fields. Add-ons inject extra fields via sqs_quote_info_extra_fields -->
            <div class="sqc-card sqc-screen-only" style="border-top:3px solid #0284c7;">
                <div class="sqc-section-kicker">Quote Info</div>
                <h2 style="font-size:20px;font-weight:800;margin:0 0 14px;">Quote Details</h2>
                <div class="sqc-qd-grid">
                    <div class="sqc-field"><label>Account / Company</label><input id="companyName" data-sqs-meta="companyName" placeholder="e.g. Acme Corp" /></div>
                    <div class="sqc-field"><label>Contact Name</label><input id="customerName" data-sqs-meta="customerName" placeholder="e.g. John Smith" /></div>
                    <div class="sqc-field"><label>Quote Date</label><input id="quoteDate" type="date" data-sqs-meta="quoteDate" /></div>
                    <div class="sqc-field"><label>Prepared By</label><input id="preparedBy" data-sqs-meta="preparedBy" placeholder="Rep name" /></div>
                    <div class="sqc-field"><label>Internal Reference</label><input id="internalRef" data-sqs-meta="internalRef" placeholder="Internal ref #" /></div>
                    <?php do_action( 'sqs_quote_info_extra_fields' ); ?>
                </div>
                <div class="sqc-field" style="margin-top:10px;"><label>Notes</label><textarea id="quoteNotes" data-sqs-meta="quoteNotes" placeholder="Internal notes, pricing assumptions..."></textarea></div>
            </div>

            <?php do_action( 'sqs_bottom_stack_extra' ); ?>

        </div><!-- /.sqc-bottom-stack -->


        </div><!-- /.sqs-calc -->

    <script>
    (function() {
    // ── Data ──────────────────────────────────────────────────────────────────
    const DATA = <?php echo $sqs_data_json; ?>;
    const SQS_QUOTE_AJAX = <?php echo wp_json_encode( array( 'url' => $sqs_quote_ajax_url, 'nonce' => $sqs_quote_nonce ) ); ?>;
    const PRICE_BREAK_HOURS = [2, 4, 8, 24, 48];

    const state = {
        product: 'tubing',
        forms: JSON.parse(JSON.stringify(DATA.defaults)),
        targets: { tgt1UnitPrice: 0, tgt2PricePerLbs: 0 }
    };

    // ── Helpers ───────────────────────────────────────────────────────────────
    function setToday() {
        const el = document.getElementById('quoteDate');
        if (!el.value) { el.value = new Date().toISOString().slice(0,10); }
    }
    function money(v) { return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(Number(v||0)); }
    function money3(v) { return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:3,maximumFractionDigits:3}).format(Number(v||0)); }
    function num(v,d=2) { return Number(v||0).toLocaleString('en-US',{minimumFractionDigits:d,maximumFractionDigits:d}); }
    function pct(v) { return num(Number(v||0)*100,2)+'%'; }
    function parseLooseNumber(value) { const c=String(value??'').replace(/[^0-9.-]/g,''); const p=Number(c); return Number.isFinite(p)?p:0; }
    function esc(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[m])); }
    function formatPercentInput(v) { return num(Number(v||0)*100,2)+'%'; }
    function formatMoneyInput(v) { return money(Number(v||0)); }
    function formatOverheadInput(v) { const p=Number(v||0)*100; return Number.isInteger(p)?p.toFixed(0)+'%':p.toFixed(2)+'%'; }
    function floorBucket(value,labels) { let b=labels[0]; for(let i=1;i<labels.length;i++){if(Number(value)<Number(labels[i]))return b;b=labels[i];}return labels[labels.length-1]; }
    function thicknessBucket(val) { const x=Number(val); if(x<0.003)return 0.002; if(x<0.004)return 0.003; if(x<0.005)return 0.004; if(x<0.006)return 0.005; return 0.006; }
    function roundDown(value,digits=0) { const m=Math.pow(10,digits); return Math.floor(Number(value)*m)/m; }
    function getPackagingCost(n) { return DATA.packaging[n]??0; }
    function getResinDensity(n) { return DATA.resinDensity[n]??0; }
    function getFormulaCost(formula,fallback) { const v=DATA.formulaCosts[formula]; return v==null?fallback:v; }

    // ── Form Builders ─────────────────────────────────────────────────────────
    function makeSelect(id,label,options,value) {
        const opts=options.map(o=>`<option value="${String(o)}"${String(o)===String(value)?' selected':''}>${o}</option>`).join('');
        return `<div class="sqc-field"><label>${label}</label><select id="${id}">${opts}</select></div>`;
    }
    function makeNumber(id,label,value,step='any') {
        return `<div class="sqc-field"><label>${label}</label><input type="number" step="${step}" id="${id}" value="${value}" /></div>`;
    }
    function makeText(id,label,value) {
        return `<div class="sqc-field"><label>${label}</label><input id="${id}" value="${value??''}" /></div>`;
    }
    function makeCombo(id,label,value) {
        return `<div class="sqc-field" style="grid-column:1/-1"><label>${label}</label><input id="${id}" list="sqc-pc-list" value="${esc(value||'')}" placeholder="Select a product code or type a custom description…" autocomplete="off" /></div>`;
    }

    function renderForms() {
        const t=state.forms.tubing;
        document.getElementById('inputs-tubing').innerHTML=`<div class="sqc-field-grid sqc-three">
            ${makeSelect('tubing-filmType','Film Type',DATA.filmTypes,t.filmType)}
            ${makeSelect('tubing-resinType','Resin Type',DATA.resinTypes,t.resinType)}
            ${makeSelect('tubing-formula','Formula',DATA.formulaOptions,t.formula)}
            ${makeNumber('tubing-resinCost','Resin Cost / LBS',t.resinCost,'0.0001')}
            ${makeNumber('tubing-width','Width (in)',t.width)}
            ${makeNumber('tubing-lengthFt','Length (ft)',t.lengthFt)}
            ${makeNumber('tubing-gauge','Gauge Thickness (in)',t.gauge,'0.0001')}
            ${makeNumber('tubing-qty','Roll Qty',t.qty)}
            ${makeNumber('tubing-operators','Operators',t.operators,'0.01')}
            ${makeNumber('tubing-scrapRate','Scrap Rate',t.scrapRate,'0.0001')}
            ${makeSelect('tubing-packaging','Packaging',Object.keys(DATA.packaging),t.packaging)}
            ${makeNumber('tubing-customPackagingFee','Custom Packaging Fee',t.customPackagingFee,'0.01')}
            ${makeNumber('tubing-specialtyCharge','Specialty Process Charge',t.specialtyCharge,'0.01')}
            ${makeNumber('tubing-toolingCharge','Tooling Charge',t.toolingCharge||0,'0.01')}
            ${makeCombo('tubing-productCode','Product Code / Description',t.productCode||'')}
        </div>`;

        const i=state.forms.inline;
        document.getElementById('inputs-inline').innerHTML=`<div class="sqc-field-grid sqc-three">
            ${makeSelect('inline-filmType','Film Type',DATA.filmTypes,i.filmType)}
            ${makeSelect('inline-resinType','Resin Type',DATA.resinTypes,i.resinType)}
            ${makeSelect('inline-formula','Formula',DATA.formulaOptions,i.formula)}
            ${makeNumber('inline-resinCost','Resin Cost / LBS',i.resinCost,'0.0001')}
            ${makeNumber('inline-width','Width (in)',i.width)}
            ${makeNumber('inline-lengthIn','Length (in)',i.lengthIn)}
            ${makeNumber('inline-gauge','Gauge Thickness (in)',i.gauge,'0.0001')}
            ${makeNumber('inline-qty','Bag Qty',i.qty)}
            ${makeNumber('inline-operators','Operators',i.operators,'0.01')}
            ${makeNumber('inline-scrapRate','Scrap Rate',i.scrapRate,'0.0001')}
            ${makeSelect('inline-packaging','Packaging',Object.keys(DATA.packaging),i.packaging)}
            ${makeNumber('inline-customPackagingFee','Custom Packaging Fee',i.customPackagingFee,'0.01')}
            ${makeNumber('inline-enclosureCharge','Enclosure Material Charge',i.enclosureCharge,'0.01')}
            ${makeNumber('inline-specialtyCharge','Specialty Process Charge',i.specialtyCharge,'0.01')}
            ${makeNumber('inline-toolingCharge','Tooling Charge',i.toolingCharge||0,'0.01')}
            ${makeCombo('inline-productCode','Product Code / Description',i.productCode||'')}
        </div>`;

        const z=state.forms.zipper;
        document.getElementById('inputs-zipper').innerHTML=`<div class="sqc-field-grid sqc-three">
            ${makeSelect('zipper-filmType','Film Type',DATA.filmTypes,z.filmType)}
            ${makeSelect('zipper-resinType','Main Resin',DATA.resinTypes,z.resinType)}
            ${makeSelect('zipper-formula','Formula',DATA.formulaOptions,z.formula)}
            ${makeNumber('zipper-resinCost','Resin Cost / LBS',z.resinCost,'0.0001')}
            ${makeNumber('zipper-width','Width (in)',z.width)}
            ${makeNumber('zipper-lengthIn','Length (in)',z.lengthIn)}
            ${makeNumber('zipper-lipIn','Lip (in)',z.lipIn)}
            ${makeNumber('zipper-gauge','Gauge Thickness (in)',z.gauge,'0.0001')}
            ${makeNumber('zipper-qty','Bag Qty',z.qty)}
            ${makeNumber('zipper-extrusionOperators','Extrusion Operators',z.extrusionOperators,'0.01')}
            ${makeText('zipper-conversionType','Off-Line Conversion',z.conversionType)}
            ${makeNumber('zipper-zipperCostPerFt','Zipper Cost / Ft',z.zipperCostPerFt,'0.0001')}
            ${makeNumber('zipper-conversionOperators','Conversion Operators',z.conversionOperators,'0.01')}
            ${makeNumber('zipper-extrusionScrapRate','Extrusion Scrap Rate',z.extrusionScrapRate,'0.0001')}
            ${makeNumber('zipper-conversionScrapRate','Conversion Scrap Rate',z.conversionScrapRate,'0.0001')}
            ${makeNumber('zipper-totalScrapRate','Total Scrap Rate',z.totalScrapRate,'0.0001')}
            ${makeSelect('zipper-packaging','Packaging',Object.keys(DATA.packaging),z.packaging)}
            ${makeNumber('zipper-customPackagingFee','Custom Packaging Fee',z.customPackagingFee,'0.01')}
            ${makeNumber('zipper-enclosureCharge','Enclosure Process Charge',z.enclosureCharge,'0.01')}
            ${makeNumber('zipper-specialtyCharge','Specialty Process Charge',z.specialtyCharge,'0.01')}
            ${makeNumber('zipper-toolingCharge','Tooling Charge',z.toolingCharge||0,'0.01')}
            ${makeNumber('zipper-customExtrusionSetupCharge','Custom Extrusion Setup',z.customExtrusionSetupCharge,'0.01')}
            ${makeNumber('zipper-customConversionSetupCharge','Custom Conversion Setup',z.customConversionSetupCharge,'0.01')}
            ${makeCombo('zipper-productCode','Product Code / Description',z.productCode||'')}
        </div>`;

        bindInputs();
    }

    function bindInputs() {
        document.querySelectorAll('.sqc-product-inputs input, .sqc-product-inputs select').forEach(el => {
            el.addEventListener('input', onInputChange);
            el.addEventListener('change', onInputChange);
        });
    }

    function syncResinCostFromFormula(product) {
        const form=state.forms[product]; if(!form)return;
        const linkedCost=getFormulaCost(form.formula,null);
        if(linkedCost==null||Number.isNaN(Number(linkedCost)))return;
        form.resinCost=Number(linkedCost);
        const el=document.getElementById(`${product}-resinCost`);
        if(el) el.value=Number(linkedCost).toFixed(4).replace(/\.0+$|(\.\d*?)0+$/,'$1');
    }

    function onInputChange(e) {
        const [product,key]=e.target.id.split('-');
        const form=state.forms[product];
        form[key]=e.target.type==='number'?Number(e.target.value):e.target.value;
        if(key==='formula') syncResinCostFromFormula(product);
        update();
    }

    // ── Calculations ──────────────────────────────────────────────────────────
    function calculateTubing(f) {
        const width=Number(f.width),lengthFt=Number(f.lengthFt),gauge=Number(f.gauge),qty=Number(f.qty);
        const resinCost=Number(f.resinCost),scrapRate=Number(f.scrapRate),operators=Number(f.operators);
        const specialtyCharge=Number(f.specialtyCharge||0),customPackagingFee=Number(f.customPackagingFee||0);
        const overheadPct=Number(f.overheadPct),density=getResinDensity(f.resinType),laborRate=DATA.laborRates.tubing;

        const cubicInchesPerRoll=width*lengthFt*12*gauge*2;
        const weightPerRoll=(cubicInchesPerRoll*16.387*density)/453.6;
        const orderWeight=qty*weightPerRoll;
        const totalWithScrap=orderWeight*(1+scrapRate);

        const setupWidth=floorBucket(width,DATA.setupWidthBuckets);
        const setupIndex=DATA.setupWidthBuckets.indexOf(setupWidth);
        const setupLbsRaw=DATA.setupLbsTable[f.filmType][setupIndex];
        const setupLbs=setupLbsRaw==='NA'?0:Number(setupLbsRaw);

        const prodWidth=floorBucket(width,DATA.prodWidthBuckets);
        const prodRate=DATA.extrusionRateTable[String(thicknessBucket(gauge))][DATA.prodWidthBuckets.indexOf(prodWidth)];
        const laborHours=orderWeight/prodRate;
        const laborCost=operators*laborHours*laborRate;

        const setupOps=Number(DATA.setupOpsExtrusion[setupIndex]);
        const setupHours=Number(DATA.setupHours[f.filmType]);
        const setupCost=setupLbs*resinCost+setupOps*laborRate*setupHours;
        const minSetupFee=Number(DATA.minimumSetupFees[setupIndex]);
        const setupCharge=Number(f.customSetupCharge)>0?Number(f.customSetupCharge):Math.max(setupCost,minSetupFee);

        const materialCost=totalWithScrap*resinCost;
        const packagingRate=getPackagingCost(f.packaging);
        const packagingCost=packagingRate*materialCost+customPackagingFee;
        const directCogs=setupCharge+materialCost+laborCost+packagingCost+specialtyCharge;
        const overhead=directCogs*overheadPct;
        const totalCost=directCogs+overhead;

        const finalMargin=Number(f.profitMargin)+Number(f.upcharge);
        const salesAmount=totalCost/(1-finalMargin);
        const unitPrice=salesAmount/qty;
        const pricePerLbs=salesAmount/orderWeight;
        const profit=salesAmount-totalCost;

        const targetSales=Number(f.targetUnitPrice)*qty;
        const targetProfit=targetSales-totalCost;
        const targetMargin=targetSales===0?0:targetProfit/targetSales;
        const targetPricePerLbs=targetSales/orderWeight;

        const priceBreaks=PRICE_BREAK_HOURS.map(hours=>{
            const qtyBreak=roundDown((hours*prodRate)/weightPerRoll,0);
            const laborBreak=hours*laborRate*operators;
            const materialBreak=(weightPerRoll*qtyBreak)*(1+scrapRate)*resinCost+(qtyBreak/qty)*specialtyCharge;
            const packagingBreak=packagingRate*(materialBreak+setupCharge+laborBreak)+customPackagingFee;
            const subtotal=setupCharge+laborBreak+materialBreak+packagingBreak+specialtyCharge;
            const salesBreak=(subtotal*(1+overheadPct))/(1-finalMargin);
            return{hours,qty:qtyBreak,unitPrice:qtyBreak?salesBreak/qtyBreak:0,pricePerLbs:qtyBreak?salesBreak/(weightPerRoll*qtyBreak):0,sales:salesBreak};
        });

        return{productName:'Tubing',unitLabel:'Price / Roll',quotedUnitLabel:'Price Per Roll',dimensions:`${num(width,2)}" x ${num(lengthFt,2)} ft x ${num(gauge,4)}"`,quantityLabel:'Roll Qty',quantity:qty,weightPerUnitLabel:'Weight Per Roll',weightPerUnit:weightPerRoll,orderWeight,totalWithScrap,productionRate:prodRate,laborHours,salesAmount,totalCost,unitPrice,pricePerLbs,profit,margin:salesAmount?profit/salesAmount:0,targetUnitPrice:Number(f.targetUnitPrice),targetSales,targetProfit,targetMargin,targetPricePerLbs,setupCharge,materialCost,laborCost,packagingCost,specialtyCharge,overhead,priceBreaks};
    }

    function calculateInline(f) {
        const width=Number(f.width),lengthIn=Number(f.lengthIn),gauge=Number(f.gauge),qty=Number(f.qty);
        const density=getResinDensity(f.resinType),laborRate=DATA.laborRates.inline;

        const cubicInches=width*(lengthIn+0.375)*gauge*2;
        const weightPerThou=(cubicInches*16.387*density)/453.6*1000;
        const orderWeight=qty*weightPerThou/1000;
        const totalWithScrap=orderWeight+(Number(f.scrapRate)*orderWeight);

        const prodWidth=floorBucket(width,DATA.prodWidthBuckets);
        const prodRate=DATA.inlineRateTable[String(thicknessBucket(gauge))][DATA.prodWidthBuckets.indexOf(prodWidth)];
        const laborHours=orderWeight/prodRate;
        const laborCost=Number(f.operators)*laborHours*laborRate;

        const setupWidth=floorBucket(width,DATA.setupWidthBuckets);
        const setupIndex=DATA.setupWidthBuckets.indexOf(setupWidth);
        const setupLbsRaw=DATA.setupLbsTable[f.filmType][setupIndex];
        const setupLbs=setupLbsRaw==='NA'?0:Number(setupLbsRaw);
        const setupOps=Number(DATA.setupOpsExtrusion[setupIndex])+Number(DATA.inlineAdditionalOps[setupIndex]);
        const setupHours=Number(DATA.setupHours[f.filmType]);
        const setupCost=setupLbs*Number(f.resinCost)+setupOps*laborRate*setupHours;
        const minSetupFee=Number(DATA.minimumSetupFees[setupIndex]);
        const setupCharge=Number(f.customSetupCharge)>0?Number(f.customSetupCharge):Math.max(setupCost,minSetupFee);

        const materialCost=totalWithScrap*Number(f.resinCost)+Number(f.enclosureCharge||0);
        const packagingCost=getPackagingCost(f.packaging)*(materialCost+laborCost)+Number(f.customPackagingFee||0);
        const directCogs=setupCharge+materialCost+laborCost+packagingCost+Number(f.specialtyCharge||0);
        const overhead=directCogs*Number(f.overheadPct);
        const totalCost=directCogs+overhead;

        const finalMargin=Number(f.profitMargin)+Number(f.upcharge);
        const salesAmount=totalCost/(1-finalMargin);
        const unitPrice=salesAmount/qty*1000;
        const pricePerLbs=salesAmount/orderWeight;
        const profit=salesAmount-totalCost;

        const targetSales=Number(f.targetUnitPrice)*qty/1000;
        const targetProfit=targetSales-totalCost;
        const targetMargin=targetSales===0?0:targetProfit/targetSales;
        const targetPricePerLbs=targetSales/orderWeight;

        const priceBreaks=PRICE_BREAK_HOURS.map(hours=>{
            const qtyBreak=(hours*prodRate)/(weightPerThou/1000);
            const materialBreak=((hours*prodRate)+((hours*prodRate)*Number(f.scrapRate)))*Number(f.resinCost)+((qtyBreak/qty)*Number(f.enclosureCharge||0));
            const laborBreak=hours*laborRate*Number(f.operators);
            const packagingBreak=getPackagingCost(f.packaging)*(materialBreak+setupCharge+laborBreak)+Number(f.customPackagingFee||0);
            const salesBreak=(((setupCharge+laborBreak+materialBreak+packagingBreak+Number(f.specialtyCharge||0))*(1+Number(f.overheadPct)))/(1-finalMargin));
            return{hours,qty:qtyBreak,unitPrice:qtyBreak?(salesBreak/qtyBreak)*1000:0,pricePerLbs:qtyBreak?salesBreak/(hours*prodRate):0,sales:salesBreak};
        });

        return{productName:'In-Line BSB',unitLabel:'Price / Thousand',quotedUnitLabel:'Price Per Thousand',dimensions:`${num(width,2)}" x ${num(lengthIn,2)}" x ${num(gauge,4)}"`,quantityLabel:'Bag Qty',quantity:qty,weightPerUnitLabel:'Weight Per Thousand',weightPerUnit:weightPerThou,orderWeight,salesAmount,totalCost,unitPrice,pricePerLbs,profit,margin:salesAmount?profit/salesAmount:0,targetUnitPrice:Number(f.targetUnitPrice),targetSales,targetProfit,targetMargin,targetPricePerLbs,setupCharge,materialCost,laborCost,packagingCost,specialtyCharge:Number(f.specialtyCharge||0),overhead,priceBreaks};
    }

    function calculateZipper(f) {
        const width=Number(f.width),lengthIn=Number(f.lengthIn),lipIn=Number(f.lipIn),gauge=Number(f.gauge),qty=Number(f.qty);
        const density=getResinDensity(f.resinType);

        const layflatWidth=width+0.5;
        const effectiveLength=lengthIn+lipIn;
        const weightPerThou=(layflatWidth*effectiveLength*gauge*2*16.387*density)/453.6*1000;
        const orderWeight=qty*weightPerThou/1000;
        const totalWithScrap=orderWeight+(Number(f.totalScrapRate)*orderWeight);

        const zipperThicknessKey=Number(f.lipIn)>=0.006?0.006:Number(f.lipIn);
        const extrusionRateWidth=floorBucket(effectiveLength,DATA.prodWidthBuckets);
        const extrusionRate=DATA.extrusionRateTable[String(zipperThicknessKey)][DATA.prodWidthBuckets.indexOf(extrusionRateWidth)];
        const extrusionLaborHours=orderWeight/extrusionRate;
        const extrusionLaborCost=Number(f.extrusionOperators)*extrusionLaborHours*DATA.laborRates.zipperExtrusion;

        const setupWidth=floorBucket(effectiveLength,DATA.setupWidthBuckets);
        const setupIndex=DATA.setupWidthBuckets.indexOf(setupWidth);
        const setupLbsRaw=DATA.setupLbsTable['PE/PP Color (Opaque)'][setupIndex];
        const setupLbs=setupLbsRaw==='NA'?0:Number(setupLbsRaw);
        const setupOps=Number(DATA.setupOpsExtrusion[setupIndex]);
        const setupHours=Number(DATA.setupHours[f.filmType]);
        const extrusionSetupCost=setupLbs*Number(f.resinCost)+setupOps*DATA.laborRates.zipperExtrusion*setupHours;
        const extrusionMinSetupFee=Number(DATA.minimumSetupFees[setupIndex]);
        const extrusionSetupCharge=Number(f.customExtrusionSetupCharge)>0?Number(f.customExtrusionSetupCharge):Math.max(extrusionSetupCost,extrusionMinSetupFee);

        const extrusionMaterialCost=totalWithScrap*Number(f.resinCost)+Number(f.enclosureCharge||0);
        const extrusionSpecialtyCost=Number(f.specialtyCharge||0);
        const extrusionCogs=extrusionSetupCharge+extrusionMaterialCost+extrusionLaborCost+extrusionSpecialtyCost;

        const conversionRateWidth=floorBucket(layflatWidth,DATA.zipperWidthBuckets);
        const qtyPerHour=DATA.zipperQtyPerHour[DATA.zipperWidthBuckets.indexOf(conversionRateWidth)];
        const zipperNeededFt=(qty+qty*Number(f.conversionScrapRate))*layflatWidth/12;
        const conversionLaborHours=Math.max(0.5,qty/qtyPerHour);
        const conversionLaborCost=conversionLaborHours*DATA.laborRates.zipperConversion*Number(f.conversionOperators);

        const conversionSetupQty=250;
        const conversionSetupResinCost=(extrusionMaterialCost/qty)*conversionSetupQty;
        const conversionSetupCost=(0.5*DATA.laborRates.zipperConversion*2)+conversionSetupResinCost;
        const conversionSetupCharge=Number(f.customConversionSetupCharge)>0?Number(f.customConversionSetupCharge):Math.max(conversionSetupCost,100);

        const zipperMaterialCost=zipperNeededFt*Number(f.zipperCostPerFt);
        const packagingCost=getPackagingCost(f.packaging)*(extrusionMaterialCost+extrusionLaborCost)+Number(f.customPackagingFee||0);
        const conversionCogs=conversionSetupCharge+zipperMaterialCost+conversionLaborCost+packagingCost;

        const directCogs=extrusionCogs+conversionCogs;
        const overhead=directCogs*Number(f.overheadPct);
        const totalCost=directCogs+overhead;

        const finalMargin=Number(f.profitMargin)+Number(f.cleanroomUpcharge);
        const salesAmount=totalCost/(1-finalMargin);
        const unitPrice=salesAmount/qty*1000;
        const pricePerLbs=salesAmount/orderWeight;
        const profit=salesAmount-totalCost;

        const targetSales=Number(f.targetUnitPrice)*qty/1000;
        const targetProfit=targetSales-totalCost;
        const targetMargin=targetSales===0?0:targetProfit/targetSales;
        const targetPricePerLbs=targetSales/orderWeight;

        const priceBreaks=PRICE_BREAK_HOURS.map(hours=>{
            const lbs=extrusionRate*hours;
            const qtyBreak=(hours*extrusionRate)/(weightPerThou/1000);
            const laborBreak=hours*DATA.laborRates.zipperExtrusion*Number(f.extrusionOperators);
            const materialBreak=(lbs+(lbs*Number(f.totalScrapRate)))*Number(f.resinCost)+((qtyBreak/qty)*Number(f.enclosureCharge||0));
            const packagingBreak=getPackagingCost(f.packaging)*(materialBreak+extrusionSetupCharge+laborBreak)+Number(f.customPackagingFee||0);
            const salesBreak=(((extrusionSetupCharge+laborBreak+materialBreak+packagingBreak+Number(f.specialtyCharge||0))*(1+Number(f.overheadPct)))/(1-finalMargin));
            return{hours,qty:qtyBreak,unitPrice:qtyBreak?(salesBreak/qtyBreak)*1000:0,pricePerLbs:lbs?salesBreak/lbs:0,sales:salesBreak};
        });

        return{productName:'Zipper',unitLabel:'Price / Thousand',quotedUnitLabel:'Price Per Thousand',dimensions:`${num(width,2)}" x ${num(lengthIn,2)}" + lip ${num(lipIn,2)}" x ${num(gauge,4)}"`,quantityLabel:'Bag Qty',quantity:qty,weightPerUnitLabel:'Weight Per Thousand',weightPerUnit:weightPerThou,orderWeight,salesAmount,totalCost,unitPrice,pricePerLbs,profit,margin:salesAmount?profit/salesAmount:0,targetUnitPrice:Number(f.targetUnitPrice),targetSales,targetProfit,targetMargin,targetPricePerLbs,setupCharge:extrusionSetupCharge+conversionSetupCharge,materialCost:extrusionMaterialCost+zipperMaterialCost,laborCost:extrusionLaborCost+conversionLaborCost,packagingCost,specialtyCharge:extrusionSpecialtyCost,overhead,priceBreaks,conversionType:f.conversionType,zipperNeededFt};
    }

    function getResults() {
        if(state.product==='tubing')return calculateTubing(state.forms.tubing);
        if(state.product==='inline')return calculateInline(state.forms.inline);
        return calculateZipper(state.forms.zipper);
    }

    // ── Render Helpers ────────────────────────────────────────────────────────
    function rowsToTable(rows) {
        return`<thead><tr><th>Item</th><th>Value</th></tr></thead><tbody>${rows.map(r=>`<tr><td>${r[0]}</td><td>${r[1]}</td></tr>`).join('')}</tbody>`;
    }

    function getCurrentTopPricingConfig() {
        if(state.product==='zipper')return{marginKey:'profitMargin',upchargeKey:'cleanroomUpcharge',upchargeLabel:'Cleanroom Upcharge',unitLabel:'Target Price Per Thousand'};
        return{marginKey:'profitMargin',upchargeKey:'upcharge',upchargeLabel:'Regular Upcharge',unitLabel:state.product==='tubing'?'Target Price Per Roll':'Target Price Per Thousand'};
    }

    function renderTopPricingCards() {
        const form=state.forms[state.product];
        const config=getCurrentTopPricingConfig();
        const r=getResults();

        // Live Pricing card — red inputs (keep in sync when not focused)
        document.getElementById('topMarginLabel').textContent='Profit Margin';
        document.getElementById('topUpchargeLabel').textContent=config.upchargeLabel;
        const pmEl=document.getElementById('topProfitMargin');
        if(document.activeElement!==pmEl) pmEl.value=formatPercentInput(form[config.marginKey]||0);
        const ucEl=document.getElementById('topUpcharge');
        if(document.activeElement!==ucEl) ucEl.value=formatPercentInput(form[config.upchargeKey]||0);

        // Read-only display cards — exact fields from spreadsheet
        const finalMargin=(Number(form[config.marginKey]||0)+Number(form[config.upchargeKey]||0));

        // Target Pricing card row 1 — green displays fed from live card inputs
        document.getElementById('topProfitMarginDisplay').textContent=formatPercentInput(form[config.marginKey]||0);
        document.getElementById('topUpchargeDisplay').textContent=formatPercentInput(form[config.upchargeKey]||0);
        document.getElementById('topFinalMarginB').textContent=pct(finalMargin);
        document.getElementById('topUnitPriceB').textContent=money(r.unitPrice);
        document.getElementById('topFinalMargin').textContent=pct(finalMargin);
        document.getElementById('topProfit').textContent=money(r.profit);
        document.getElementById('topUnitPriceLabel').textContent=r.unitLabel;
        document.getElementById('topUnitPrice').textContent=money(r.unitPrice);
        document.getElementById('topPricePerLbs').textContent=money3(r.pricePerLbs);
        document.getElementById('topTotalSales').textContent=money(r.salesAmount);

        // ── Target Row 1: Price Per Roll input ──────────────────────────
        document.getElementById('tgt1UnitLabel').textContent='Target '+r.unitLabel;
        document.getElementById('tgt2UnitLabel').textContent=r.unitLabel;
        const tgt1El=document.getElementById('tgt1UnitPrice');
        if(document.activeElement!==tgt1El) tgt1El.value=formatMoneyInput(state.targets.tgt1UnitPrice);

        const tgt1Qty = state.product==='tubing' ? r.quantity : (r.quantity/1000);
        const tgt1Sales = state.targets.tgt1UnitPrice * tgt1Qty;
        const tgt1Profit = tgt1Sales - r.totalCost;
        const tgt1Margin = tgt1Sales > 0 ? tgt1Profit / tgt1Sales : 0;
        const tgt1Lbs   = r.orderWeight > 0 ? tgt1Sales / r.orderWeight : 0;
        document.getElementById('tgt1Profit').textContent=money(tgt1Profit);
        document.getElementById('tgt1PricePerLbs').textContent=money3(tgt1Lbs);
        document.getElementById('tgt1TotalSales').textContent=money(tgt1Sales);
        document.getElementById('tgt1Margin').textContent=pct(tgt1Margin);

        // ── Target Row 2: Price Per LBS input ───────────────────────────
        const tgt2El=document.getElementById('tgt2PricePerLbs');
        if(document.activeElement!==tgt2El) tgt2El.value=formatMoneyInput(state.targets.tgt2PricePerLbs);

        const tgt2Sales  = state.targets.tgt2PricePerLbs * r.orderWeight;
        const tgt2UnitP  = tgt1Qty > 0 ? tgt2Sales / tgt1Qty : 0;
        const tgt2Profit = tgt2Sales - r.totalCost;
        const tgt2Margin = tgt2Sales > 0 ? tgt2Profit / tgt2Sales : 0;
        document.getElementById('tgt2Profit').textContent=money(tgt2Profit);
        document.getElementById('tgt2UnitPrice').textContent=money(tgt2UnitP);
        document.getElementById('tgt2TotalSales').textContent=money(tgt2Sales);
        document.getElementById('tgt2Margin').textContent=pct(tgt2Margin);
    }

    function renderResults() {
        const r=getResults();
        renderTopPricingCards();
        document.getElementById('productLabel').textContent=r.productName;
        document.getElementById('inputTitle').textContent=r.productName+' Variables';
        document.getElementById('heroSales').textContent=money(r.salesAmount);
        document.getElementById('kpiCost').textContent=money(r.totalCost);
        document.getElementById('heroNetRevenue').textContent=money(r.profit);
        document.getElementById('kpiUnitLabel').textContent=r.unitLabel;
        document.getElementById('kpiUnitPrice').textContent=money(r.unitPrice);
        document.getElementById('kpiMargin').textContent=pct(r.margin);

        const activeForm=state.forms[state.product];
        const customSetupValue=state.product==='zipper'?Number(activeForm.customExtrusionSetupCharge||0):Number(activeForm.customSetupCharge||0);

        document.getElementById('marginOutputTable').innerHTML=rowsToTable([
            [r.quotedUnitLabel,money(r.unitPrice)],['Total Sales',money(r.salesAmount)],
            ['Profit',money(r.profit)],['Profit Margin',pct(r.margin)],
            ['Price Per LBS',money3(r.pricePerLbs)],[r.weightPerUnitLabel,num(r.weightPerUnit,4)+' lbs'],
            ['Order Weight',num(r.orderWeight,4)+' lbs'],['Custom Setup Charge',money(customSetupValue)],
            ['Setup Charge',money(r.setupCharge)],['Material Cost',money(r.materialCost)],
            ['Labor Cost',money(r.laborCost)],['Packaging Cost',money(r.packagingCost)],
            ['COGS Overhead %',formatOverheadInput(activeForm.overheadPct||0)],['Overhead',money(r.overhead)],
        ]);

        document.getElementById('targetOutputTable').innerHTML=rowsToTable([
            [r.quotedUnitLabel,money(r.targetUnitPrice)],['Target Sales',money(r.targetSales)],
            ['Target Profit',money(r.targetProfit)],['Target Margin',pct(r.targetMargin)],
            ['Target Price Per LBS',money3(r.targetPricePerLbs)],['Dimensions',r.dimensions],
            [r.quantityLabel,num(r.quantity,0)],
        ]);

        document.getElementById('priceBreakTable').innerHTML=`<thead><tr>
            <th>Prod Time (hrs)</th><th>Qty</th><th>${r.quotedUnitLabel}</th><th>Price / LBS</th><th>Total Sales</th>
        </tr></thead><tbody>${r.priceBreaks.map(row=>`<tr>
            <td>${num(row.hours,0)}</td><td>${num(row.qty,0)}</td>
            <td>${money(row.unitPrice)}</td><td>${money(row.pricePerLbs)}</td><td>${money(row.sales)}</td>
        </tr>`).join('')}</tbody>`;

        // Broadcast results to add-on modules
        document.dispatchEvent(new CustomEvent('sqs:update', {detail:{result:r, state:JSON.parse(JSON.stringify(state)), quoteNumber:currentQuoteNumber}}));
    }

    let currentQuoteId = 0;
    let currentQuoteNumber = '';
    let currentRevisionLabel = 'Rev A';

    function postQuoteAction(action, data={}) {
        const body = new URLSearchParams();
        body.set('action', action);
        body.set('nonce', SQS_QUOTE_AJAX.nonce);
        Object.entries(data).forEach(([key,value]) => body.set(key, value));
        return fetch(SQS_QUOTE_AJAX.url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body
        }).then(r => r.json()).then(json => {
            if (!json || !json.success) throw new Error(json?.data?.message || 'Request failed.');
            return json.data;
        });
    }

    function getQuoteMeta() {
        // Collect all fields tagged data-sqs-meta — core fields + any injected by add-ons
        const meta = {};
        document.querySelectorAll('[data-sqs-meta]').forEach(el => {
            meta[el.dataset.sqsMeta] = el.value || '';
        });
        // Always include the system-managed quote number
        meta.quoteNumber = (document.getElementById('quoteNumber')||{}).value || currentQuoteNumber || '';
        return meta;
    }

    function buildQuotePayload(newRevision=false) {
        const r = getResults();
        return {
            quote_id: currentQuoteId || 0,
            new_revision: !!newRevision,
            status: document.getElementById('quoteStatus')?.value || 'Draft',
            product: state.product,
            state: JSON.parse(JSON.stringify(state)),
            meta: getQuoteMeta(),
            result: r,
            saved_at: new Date().toISOString()
        };
    }

    function setSaveStamp(text, isGood=true) {
        const el = document.getElementById('quoteSaveStamp');
        if (!el) return;
        el.textContent = text;
        el.style.color = isGood ? '#166534' : '#b91c1c';
        el.style.borderColor = isGood ? '#bbf7d0' : '#fecaca';
        el.style.background = isGood ? '#f0fdf4' : '#fef2f2';
    }

    function saveCurrentQuote(newRevision=false) {
        setSaveStamp(newRevision ? 'Saving revision...' : 'Saving...', true);
        return postQuoteAction('sqs_save_quote', {payload: JSON.stringify(buildQuotePayload(newRevision))})
            .then(data => {
                currentQuoteId = Number(data.quote_id || 0);
                currentQuoteNumber = data.quote_number || currentQuoteNumber;
                currentRevisionLabel = data.revision_label || currentRevisionLabel;
                document.getElementById('quoteNumber').value = currentQuoteNumber;
                document.getElementById('quoteStatus').value = data.status || 'Draft';
                setSaveStamp(`${currentQuoteNumber} ${currentRevisionLabel}`);
                return data;
            })
            .catch(err => { setSaveStamp(err.message || 'Save failed', false); alert(err.message || 'Save failed.'); });
    }

    function renderQuoteList(quotes) {
        const body = document.getElementById('sqsLoadBody');
        if (!quotes || !quotes.length) {
            body.innerHTML = '<p style="margin:0;color:#64748b;font-weight:700;">No saved quotes yet.</p>';
            return;
        }
        body.innerHTML = `<table class="sqs-load-table"><thead><tr><th>Quote</th><th>Customer / Company</th><th>Product</th><th>Status</th><th>Total</th><th>Updated</th><th></th></tr></thead><tbody>${quotes.map(q=>`
            <tr>
                <td><strong>${esc(q.quote_number || '')}</strong><br><span style="color:#64748b;">${esc(q.revision_label || '')}</span></td>
                <td>${esc(q.customer_name || '—')}<br><span style="color:#64748b;">${esc(q.company_name || '')}</span></td>
                <td>${esc(q.product_type || '')}</td>
                <td>${esc(q.status || 'Draft')}</td>
                <td>${money(q.total_sales || 0)}</td>
                <td>${esc(q.updated_at || '')}</td>
                <td><button type="button" class="sqs-load-open" data-id="${esc(q.id)}">Open</button></td>
            </tr>`).join('')}</tbody></table>`;
    }

    function openLoadModal() {
        const modal = document.getElementById('sqsLoadModal');
        modal.style.display = 'flex';
        document.getElementById('sqsLoadBody').innerHTML = 'Loading saved quotes...';
        postQuoteAction('sqs_load_quotes').then(data => renderQuoteList(data.quotes || [])).catch(err => {
            document.getElementById('sqsLoadBody').innerHTML = `<p style="color:#b91c1c;font-weight:800;">${esc(err.message || 'Could not load quotes.')}</p>`;
        });
    }

    function closeLoadModal() { document.getElementById('sqsLoadModal').style.display = 'none'; }

    function applyLoadedQuote(data) {
        const payload = data.payload || {};
        const quote = data.quote || {};
        if (payload.state) {
            state.product = payload.state.product || 'tubing';
            state.forms = payload.state.forms || JSON.parse(JSON.stringify(DATA.defaults));
            state.targets = payload.state.targets || {tgt1UnitPrice:0,tgt2PricePerLbs:0};
        }
        const meta = payload.meta || {};
        // Restore all meta fields (core + any add-on injected fields).
        // Fall back to the element's HTML default value so fields with
        // value="..." in markup keep their default when loaded from older
        // quotes that didn't save that key.
        document.querySelectorAll('[data-sqs-meta]').forEach(el => {
            const key = el.dataset.sqsMeta;
            if (Object.prototype.hasOwnProperty.call(meta, key)) {
                el.value = meta[key] || el.defaultValue || '';
            }
        });
        currentQuoteId = Number(quote.id || 0);
        currentQuoteNumber = quote.quote_number || meta.quoteNumber || '';
        currentRevisionLabel = quote.revision_label || payload.revision_label || 'Rev A';
        document.getElementById('quoteNumber').value = currentQuoteNumber;
        document.getElementById('quoteStatus').value = quote.status || payload.status || 'Draft';
        renderForms();
        switchProduct(state.product || 'tubing');
        setSaveStamp(`${currentQuoteNumber} ${currentRevisionLabel}`);
        closeLoadModal();
    }

    function update() { renderResults(); }

    function switchProduct(product) {
        state.product=product;
        document.querySelectorAll('.sqc-switch-btn').forEach(btn=>btn.classList.toggle('sqc-active',btn.dataset.product===product));
        document.querySelectorAll('.sqc-product-inputs').forEach(el=>el.classList.add('sqc-hidden'));
        document.getElementById(`inputs-${product}`).classList.remove('sqc-hidden');
        update();
    }

    function wireTopFields() {
        // Bind update to all meta fields (core + any add-on injected fields)
        document.querySelectorAll('[data-sqs-meta]').forEach(el => el.addEventListener('input', update));

        function bindFormattedField(id,parser,applyValue) {
            const el=document.getElementById(id);
            ['change','blur'].forEach(evt=>el.addEventListener(evt,e=>{applyValue(parser(e.target.value));update();}));
        }

        // Profit Margin and Upcharge inputs live in the Target Pricing card — wire them here
        bindFormattedField('topProfitMargin', v=>parseLooseNumber(v)/100, parsed=>{
            state.forms[state.product][getCurrentTopPricingConfig().marginKey]=parsed;
        });
        bindFormattedField('topUpcharge', v=>parseLooseNumber(v)/100, parsed=>{
            state.forms[state.product][getCurrentTopPricingConfig().upchargeKey]=parsed;
        });

        // Target row inputs
        function bindTargetField(id, applyValue) {
            const el=document.getElementById(id);
            ['change','blur'].forEach(evt=>el.addEventListener(evt,e=>{
                applyValue(parseLooseNumber(e.target.value));
                update();
            }));
        }
        bindTargetField('tgt1UnitPrice', parsed=>{ state.targets.tgt1UnitPrice=parsed; });
        bindTargetField('tgt2PricePerLbs', parsed=>{ state.targets.tgt2PricePerLbs=parsed; });

        const printBtn=document.getElementById('printBtn'); if(printBtn){ printBtn.addEventListener('click',()=>window.print()); }
        document.getElementById('saveQuoteBtn').addEventListener('click',()=>saveCurrentQuote(false));
        document.getElementById('newRevisionBtn').addEventListener('click',()=>saveCurrentQuote(true));
        document.getElementById('loadQuoteBtn').addEventListener('click',openLoadModal);
        document.getElementById('sqsLoadClose').addEventListener('click',closeLoadModal);
        document.getElementById('sqsLoadModal').addEventListener('click',e=>{ if(e.target.id==='sqsLoadModal') closeLoadModal(); });
        document.getElementById('sqsLoadBody').addEventListener('click',e=>{
            const btn=e.target.closest('.sqs-load-open');
            if(!btn) return;
            postQuoteAction('sqs_get_quote',{quote_id:btn.dataset.id}).then(applyLoadedQuote).catch(err=>alert(err.message||'Could not open quote.'));
        });
        document.getElementById('quoteStatus').addEventListener('change',()=>setSaveStamp(currentQuoteNumber ? 'Status changed' : 'Unsaved', true));
        document.getElementById('resetDefaultsBtn').addEventListener('click',()=>{
            currentQuoteId=0; currentQuoteNumber=''; currentRevisionLabel='Rev A';
            state.forms=JSON.parse(JSON.stringify(DATA.defaults));
            renderForms();
            update();
            setSaveStamp('Unsaved', true);
        });
        document.querySelectorAll('.sqc-switch-btn').forEach(btn=>{
            btn.addEventListener('click',()=>switchProduct(btn.dataset.product));
        });
    }

    // ── Product Code Datalist ─────────────────────────────────────────────────
    function buildProductCodeDatalist() {
        const codes = (DATA.productCodes || []);
        if ( ! codes.length ) return;
        const opts = codes.map(function(pc) {
            return '<option value="' + esc(pc.code + ' — ' + pc.desc) + '">';
        }).join('');
        const dl = document.createElement('datalist');
        dl.id = 'sqc-pc-list';
        dl.innerHTML = opts;
        document.body.appendChild(dl);
    }

    // ── Boot ──────────────────────────────────────────────────────────────────
    setToday();
    buildProductCodeDatalist();
    renderForms();
    // resinCost uses the user-entered default value; syncing only happens on formula dropdown change
    wireTopFields();
    switchProduct('tubing');

    // Add-on modules can hook into saved quote data through the core quote API.




    // ── Public API for add-on modules ─────────────────────────────────────────
    window.SQS = {
        money:  money,
        money3: money3,
        num:    num,
        pct:    pct,
        esc:    esc,
        getState:   function() { return state; },
        getResults: getResults,
        getQuoteNumber: function() { return currentQuoteNumber; }
    };

    })(); // end IIFE
    </script>
    <?php
}
