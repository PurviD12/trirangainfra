import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { BigBuyerEmailComponent } from './big-buyer-email.component';

describe('BigBuyerEmailComponent', () => {
  let component: BigBuyerEmailComponent;
  let fixture: ComponentFixture<BigBuyerEmailComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ BigBuyerEmailComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(BigBuyerEmailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
