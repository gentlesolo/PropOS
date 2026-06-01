import {device, element, by, expect as detoxExpect} from 'detox';

describe('Login flow', () => {
  beforeAll(async () => {
    await device.launchApp({newInstance: true});
  });

  afterAll(async () => {
    await device.terminateApp();
  });

  it('shows login screen on first launch', async () => {
    await detoxExpect(element(by.text('PropOS'))).toBeVisible();
    await detoxExpect(element(by.text('Agent Field App'))).toBeVisible();
  });

  it('shows error on wrong credentials', async () => {
    await element(by.id('email-input')).typeText('wrong@example.com');
    await element(by.id('password-input')).typeText('wrongpassword');
    await element(by.text('Sign in')).tap();
    await detoxExpect(element(by.text('Check your email and password and try again.'))).toBeVisible();
  });

  it('navigates to home screen on correct credentials', async () => {
    await element(by.id('email-input')).clearText();
    await element(by.id('email-input')).typeText('agent@demo.propos.com');
    await element(by.id('password-input')).clearText();
    await element(by.id('password-input')).typeText('DemoPass123!');
    await element(by.text('Sign in')).tap();
    await detoxExpect(element(by.text('Home'))).toBeVisible();
  });
});
