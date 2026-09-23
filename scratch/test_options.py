from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={'width': 1920, 'height': 1080})
    page.goto('http://localhost/dashboard_heat/dashboard_heat', wait_until='domcontentloaded', timeout=60000)
    page.wait_for_timeout(4000)

    page.screenshot(path='scratch/dashboard_final.png')
    browser.close()
print('Final dashboard screenshot captured successfully')
